<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Controllers\Traits;

/**
 * Task progress trait
*/
trait TaskProgress 
{        
    /**
     * Current task progress step
     *
     * @var integer
     */
    protected $progressStep = 0;

    /**
     * Total task progress steps null for unknow
     *
     * @var int|null
     */
    protected $totalProgressSteps = null;

    /**
     * Delay value
     *
     * @var int|null
     */
    protected $progressSleep = null;

    /**
     * Init task progress response 
     *
     * @param int|null $totalSteps  
     * @param int|null $sleep  
     * @return void
     */
    public function initTaskProgress($totalSteps = null, $sleep = null)
    {
        \ini_set('output_buffering','Off'); 
        \ini_set('zlib.output_compression',0);
    
        $this->progressStep = 0;
        $this->progressSleep = $sleep; 
        $this->totalProgressSteps = $totalSteps;
        
        \header('Connection: close;');
        \header('Content-Encoding: none;');          
    }

    /**
     * Set end task progress
     *
     * @return void
     */
    public function taskProgressEnd()
    {
        \ob_end_clean();
        $this->clearResult();
        $this->field('progress_end',true);
    }

    /**
     * Flush progress response
     *
     * @return void
     */
    public function sendProgressResponse()
    {   
        \ob_end_clean();
        \ob_start();  
       
        // set task progress fiedl
        $this->field('progress',true);
        $this->progressStep++;

        $this->field('progress_step',$this->progressStep);
        $this->field('progress_total_setps',$this->totalProgressSteps);

        $response = $this->getResponse();
        $body = $response->getBody();

        $beginCode = '';
        $separator = ',';
        if ($this->progressStep == 1) {
            $beginCode = '[';
            $separator = '';
        }
    
        echo $beginCode . $separator . $body;
        
        \ob_flush();      
        \flush();
        \ob_clean();

        if (empty($this->progressSleep) == false) {
            \sleep($this->progressSleep);
        }
    }
}
