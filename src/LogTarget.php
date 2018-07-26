<?php
/**
 * Stackdriver Google Cloud Platform Log Target.
 *
 * @category   Yii
 * @package    GoogleCloudPlatform    
 * @subpackage Logs
 * @author     Brando Meniconi <b.meniconi@silverbackstudio.it>
 * @license    BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause 
 * @link       http://www.silverbackstudio.it/
 */

namespace yii\stackdriver;

use Yii;
use yii\helpers\VarDumper;
use yii\log\Target;
use yii\log\Logger;

use Google\Cloud\Logging\LoggingClient;
use Google\Cloud\Core\Report\SimpleMetadataProvider;

/**
 * SyslogTarget writes log to syslog.
 *
 * @author miramir <gmiramir@gmail.com>
 * @since 2.0
 */
class LogTarget extends Target
{
    /**
     * @var string stackdriver log name
     */
    public $logName = 'application-log';
    
    /**
     * @var string stackdriver service name
     */    
    public $serviceName = 'application';
    
    /**
     * @var string stackdriver service version
     */    
    public $serviceVersion = '1.0';
    
    /**
     * @var string stackdriver service version
     */
    protected $metadataProvider = null;
    
    /**
     * @var Google\Cloud\Core\Report\SimpleMetadataProvider Google Cloud Metadata Provider
     */     
    protected $psrLogger = null;
    

    /**
     * @var array syslog levels
     */
    private $_syslogLevels = [
        Logger::LEVEL_TRACE => 'debug',
        Logger::LEVEL_PROFILE_BEGIN => 'debug',
        Logger::LEVEL_PROFILE_END => 'debug',
        Logger::LEVEL_PROFILE => 'debug',
        Logger::LEVEL_INFO => 'info',
        Logger::LEVEL_WARNING => 'warning',
        Logger::LEVEL_ERROR => 'error',
    ];


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        $loggingClient = new LoggingClient();
        $this->metadataProvider = new SimpleMetadataProvider([], '', $this->serviceName, $this->serviceVersion);
        
        $this->psrLogger = $loggingClient->psrLogger($this->logName, [
            'batchEnabled' => true,
            'metadataProvider' => $this->metadataProvider,
            'batchOptions' => [
                'numWorkers' => 2
            ]
        ]);
    }

    /**
     * Writes log messages to syslog.
     * Starting from version 2.0.14, this method throws LogRuntimeException in case the log can not be exported.
     * @throws LogRuntimeException
     */
    public function export()
    {

        foreach ($this->messages as $message) {
		    
            list($text, $level, $category, $timestamp) = $message;

		    $context = array( 
		        'category' => $category
		    );
		   
            $app = Yii::$app;
            
            if($app instanceof \yii\web\Application) {
    		    $context['httpRequest'] = array(
    		        'requestUrl' => $app->request->getUrl(),
    		        'requestMethod' => $app->request->getMethod(),
    		        'referer' => $app->request->getReferrer(),
    		        'userAgent' => $app->request->getUserAgent(),
    		        'remoteIp' => $app->request->getRemoteIP(),
    		    );
            }
		
			$this->psrLogger->log( $this->_syslogLevels[$level], $text, $context );
		}        
        
    }

}