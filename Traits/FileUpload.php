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

use Arikaim\Core\Utils\Path;

/**
 * File upload trait
*/
trait FileUpload 
{        
    /**
     * Get file upload message name
     *
     * @return string
     */
    protected function getFileUploadMessage(): string
    {
        return $this->fileUploadMessage ?? 'upload';
    }

    /**
     * Get field name
     *
     * @return string
     */
    public function getUplaodFieldName(): string
    {
        return $this->uploadFiledName ?? 'file';
    }

    /**
     * File upload
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function uploadController($request, $response, $data)
    {
        $this->requireControlPanelPermission();

        $this->onDataValid(function($data) use ($request) {                
            $destinationPath = $data->get('path','');
            $files = $this->uploadFiles($request,$destinationPath);
               
            $this->setResponse(\is_array($files),function() use($files) {                  
                $this
                    ->message($this->getFileUploadMessage())
                    ->field('files',$files);                                  
            },'errors.upload');           
        });
        $data->validate();          
    }

    /**
     * Upload file(s)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param string $path Destination path relative to storage path
     * @param boolean $relative
     * @param boolean $moveFile
     * @param string|null $destinationFileName
     * @return array
     */
    public function uploadFiles(
        $request, 
        string $path = '', 
        bool $relative = true, 
        bool $moveFile = true,
        ?string $destinationFileName = null
    ): array
    {
        $fieldName = $this->getUplaodFieldName();
        $files = $request->getUploadedFiles();
        $destinationPath = ($relative == true) ? Path::STORAGE_PATH . $path : $path;
        $uploadedFiles = (\is_object($files[$fieldName]) == true) ? [$files[$fieldName]] : $files[$fieldName];
    
        $result = [];
        foreach ($uploadedFiles as $file) {
            $destinationFileName = (empty($destinationFileName) == true) ? $file->getClientFilename() : $destinationFileName;

            if ($file->getError() === UPLOAD_ERR_OK) {                   
                $fileName = $destinationPath . $destinationFileName;   
                if ($moveFile == true) {
                    $file->moveTo($fileName);       
                }         
            }

            $result[] = [
                'name'       => $destinationFileName,              
                'size'       => $file->getSize(),              
                'file_name'  => $destinationFileName, 
                'media_type' => $file->getClientMediaType(),
                'moved'      => $moveFile,
                'error'      => $file->getError()
            ];         
        }

        return $result;
    }
}
