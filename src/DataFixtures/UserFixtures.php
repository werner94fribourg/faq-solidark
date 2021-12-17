<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserFixtures extends Fixture
{
    public function __construct(UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger, Filesystem $filesystem, $fixturesFilesDirectory, $targetPhotoDirectory, $targetCVDirectory)
    {
        $this->passwordHasher = $passwordHasher;
        $this->slugger = $slugger;
        $this->filesystem = $filesystem;
        $this->fixturesFilesDirectory = $fixturesFilesDirectory;
        $this->targetPhotoDirectory = $targetPhotoDirectory;
        $this->targetCVDirectory = $targetCVDirectory;
    }
    public function load(ObjectManager $manager): void
    {
        $id = 1;
        foreach($this->getUserData() as [$email, $roles, $password, $username, $last_name, $first_name, $occupation, $profilePicture, $CV])
        {
            $user = new User();
            $user->setEmail($email);
            $user->setRoles($roles);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setUsername($username);
            $user->setLastName($last_name);
            $user->setFirstName($first_name);
            $user->setOccupation($occupation);
            //Set profile picture
            $this->filesystem->copy($this->fixturesFilesDirectory.$profilePicture, $this->fixturesFilesDirectory.$id.$profilePicture);
            $profilePictureFile = new File($this->fixturesFilesDirectory.$id.$profilePicture);
            $profilePicturePath = $this->uploadProfilePhoto($profilePictureFile);
            $user->setProfilePicture($profilePicturePath);

            //Set CV
            $this->filesystem->copy($this->fixturesFilesDirectory.$CV, $this->fixturesFilesDirectory.$id.$CV);
            $CVFile = new File($this->fixturesFilesDirectory.$id.$CV);
            $CVPath = $this->uploadCV($CVFile);
            $user->setCV($CVPath);
            $user->setIsVerified(true);
            $manager->persist($user);
            $id++;
            
        }

        $manager->flush();
    }

    public function getUserData(): array
    {
        return [
            ['superadmin@solidark.ch', ['ROLE_SUPERADMIN'], 'uB*E.R@Ly)mbj6<:', 'superadmin_solidark', 'Superadmin', 'Solidark', 'Forum superadmin', 'default_profile_picture.jpg', 'default_CV.pdf'],
            ['admin@solidark.ch', ['ROLE_ADMIN'], 'uB*E.R@Ly)mbj6<:', 'admin_solidark', 'Admin', 'Solidark', 'Forum admin', 'default_profile_picture.jpg', 'default_CV.pdf'],
        ];
    }

    private function uploadProfilePhoto(File $file)
    {
        $originalFilename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $filename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        try {
            $file->move('public/'.$this->targetPhotoDirectory, $filename);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }
        return $this->targetPhotoDirectory.$filename;
    }

    private function uploadCV(File $file)
    {
        $originalFilename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $filename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        try {
            $file->move('public/'.$this->targetCVDirectory, $filename);
        } catch(FileException $e)
        {
            // ... handle exception if something happens during file upload
        }
        return $this->targetCVDirectory.$filename;
    }
}
