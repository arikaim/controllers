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

use Arikaim\Core\Utils\File;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;

/**
 * File download and image view trait
*/
trait FileDownload 
{            
    /**
     * Download file
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $fileName
     * @param \Psr\Http\Message\StreamInterface|string $stream
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function downloadFileHeaders($response, $fileName, $stream)
    {
        $stream = ($stream instanceof StreamInterface) ? $stream : Psr7\stream_for($stream);

        return $response
            ->withHeader('Content-Type','application/force-download')
            ->withHeader('Content-Type','application/octet-stream')
            ->withHeader('Content-Type','application/download')
            ->withHeader('Content-Description','File Transfer')
            ->withHeader('Content-Transfer-Encoding','binary')
            ->withHeader('Content-Disposition','attachment; filename="' . basename($fileName) . '"')
            ->withHeader('Expires','0')
            ->withHeader('Cache-Control','must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Pragma','public')
            ->withBody($stream); 
    } 

    /**
     * Download file
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $filePath  
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function downloadFile($response, $filePath)
    {
        if ($this->get('storage')->has($filePath) == true) {
            $data = $this->get('storage')->read($filePath);  
            $fileName = basename($filePath);        
        } 

        return $this->downloadFileHeaders($response,$fileName,$data);
    } 

    /**
     * View image headers
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $type Content type
     * @param  \Psr\Http\Message\StreamInterface|string $stream
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function viewImageHeaders($response, $type, $stream)
    {
        $stream = ($stream instanceof StreamInterface) ? $stream : Psr7\stream_for($stream);

        return $response
            ->withHeader('Content-Type',$type)                      
            ->withHeader('Expires','0')
            ->withHeader('Pragma','public')
            ->withBody($stream); 
    }

    /**
     * View image
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $imagePath 
     * @param string|null $imgeNotFoundPath 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function viewImage($response, $imagePath, $imgeNotFoundPath = null)
    {
        if ($this->get('storage')->has($imagePath) == true) {
            $data = $this->get('storage')->read($imagePath);
            $type = File::getMimetype($this->get('storage')->getFuillPath($imagePath));
        } else {
            $data = $this->get('storage')->read($imgeNotFoundPath);
            $type = File::getMimetype($this->get('storage')->getFuillPath($imgeNotFoundPath)); 
        }
        
        return $this->viewImageHeaders($response,$type,$data);
    }
}
