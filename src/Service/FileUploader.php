<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private $targetPhotoDirectory;
    private $targetCVDirectory;
    private $slugger;

    public function __construct(string $targetPhotoDirectory, string $targetCVDirectory, SluggerInterface $slugger)
    {
        $this->targetPhotoDirectory = $targetPhotoDirectory;
        $this->targetCVDirectory = $targetCVDirectory;
        $this->slugger = $slugger;
    }

    public function uploadPhoto(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $filename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetPhotoDirectory(), $filename);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return $this->getTargetPhotoDirectory().$filename;
    }

    public function uploadCV(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $filename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetCVDirectory(), $filename);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return $this->getTargetCVDirectory().$filename;
    }

    public function getTargetPhotoDirectory()
    {
        return $this->targetPhotoDirectory;
    }

    public function getTargetCVDirectory()
    {
        return $this->targetCVDirectory;
    }
}