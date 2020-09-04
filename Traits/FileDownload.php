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

use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;

use Arikaim\Core\Utils\File;

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
            ->withHeader('Content-Disposition','attachment; filename="' . \basename($fileName) . '"')
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
     * @param string filesystem
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function downloadFile($response, $filePath, $filesystem = 'storage')
    {
        if ($this->get('storage')->has($filePath,$filesystem) == true) {
            $data = $this->get('storage')->readStream($filePath,$filesystem);  
            $fileName = basename($filePath);  
           
            return $this->downloadFileHeaders($response,$fileName,$data);            
        } 
        
        return $response;
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
     * @param string $filesystem 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function viewImage($response, $imagePath, $imgeNotFoundPath = null, $filesystem = 'storage')
    {
        if ($this->get('storage')->has($imagePath,$filesystem) == true) {
            $data = $this->get('storage')->read($imagePath,$filesystem);
            $type = File::getMimetype($this->get('storage')->getFullPath($imagePath,$filesystem));
        } else {
            $data = $this->get('storage')->read($imgeNotFoundPath,$filesystem);
            $type = File::getMimetype($this->get('storage')->getFullPath($imgeNotFoundPath,$filesystem)); 
        }
        
        return $this->viewImageHeaders($response,$type,$data);
    }
}
