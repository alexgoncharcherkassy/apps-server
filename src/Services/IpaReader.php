<?php

namespace App\Services;

use CFPropertyList\CFPropertyList;
use CFPropertyList\IOException;

class IpaReader
{
    private $to = '/tmp/';
    private $tmpDir;
    private $info = [];
    private $icon;
    private $plist;
    private $basePath;

    /**
     * init function.
     * @param $path
     * @throws \Exception
     */
    public function read($path)
    {
        if (!file_exists($path)) {
            throw new \Exception('Ipa File not found');
        }

        $this->unZipFiles($path);
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getBundleName()
    {
        return $this->info['CFBundleName'];
    }

    public function getBundleVersion()
    {
        return $this->info['CFBundleVersion'];
    }

    public function getMinimumOSVersion()
    {
        return $this->info['MinimumOSVersion'];
    }

    public function getPlatformVersion()
    {
        return $this->info['DTPlatformVersion'];
    }

    public function getBundleIdentifier()
    {
        return $this->info['CFBundleIdentifier'];
    }

    public function getBundleDisplayName()
    {
        return $this->info['CFBundleDisplayName'];
    }

    public function getBundleShortVersionString()
    {
        return $this->info['CFBundleShortVersionString'];
    }

    public function getBundleSupportedPlatforms()
    {
        return $this->info['CFBundleSupportedPlatforms'];
    }

    public function getSupportedInterfaceOrientations()
    {
        return $this->info['UISupportedInterfaceOrientations'];
    }

    public function getAppServer()
    {
        return $this->info['APP_SERVER'];
    }

    /**
     * @param $file
     * @throws \Exception
     */
    private function unZipFiles($file)
    {
        $this->tmpDir = $this->to . microtime(true);

        mkdir($this->tmpDir, 0777, true);
        $zip = new \ZipArchive();
        $zip->open($file);
        $icon = null;
        $plist = null;

        $unpackFiles = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $filename = $zip->getNameIndex($i);
            if ((strpos($filename, 'Info.plist') > 0) && substr_count($filename, '/') == 2) {
                $plist = $filename;
                $unpackFiles[] = $filename;
            }
            if ((strpos($filename, '.png') !== false) && substr_count($filename, '/') == 2) {
                $unpackFiles[] = $filename;
            }
        }

        $zip->extractTo($this->tmpDir, $unpackFiles);

        $this->basePath = $this->tmpDir . '/' . pathinfo($plist, PATHINFO_DIRNAME);

        $this->plist = $this->tmpDir . '/' . $plist;
        $this->readInfoPlist();
    }

    /**
     * @throws \Exception
     */
    private function readInfoPlist()
    {
        $info = [];
        $info['CFBundleName'] = null;
        $info['MinimumOSVersion'] = null;
        $info['MinimumOSVersion'] = null;
        $info['CFBundleVersion'] = null;
        $info['CFBundleShortVersionString'] = null;
        $info['CFBundleSupportedPlatforms'] = null;
        $info['UISupportedInterfaceOrientations'] = null;
        $info['CFBundleDisplayName'] = null;
        $info['DTPlatformVersion'] = null;
        $info['CFBundleIdentifier'] = null;
        $info['APP_SERVER'] = null;

        if (is_file($this->plist)) {
            try {
                $plist = new CFPropertyList($this->plist);
            } catch (IOException $e) {
                throw new \Exception('File could not be read');
            }
            $plist = $plist->toArray();

            $iconGroups = [];
            foreach ($plist as $key => $value) {
                if (strpos($key, 'CFBundleIcons') === 0) {
                    $iconGroups[] = $value;
                }
            }

            $this->icon = $this->getIconPath($iconGroups);

            $info['CFBundleName'] = array_key_exists('CFBundleName', $plist) ? $plist['CFBundleName'] : 'CFBundleName';
            $info['CFBundleVersion'] = array_key_exists('CFBundleVersion', $plist) ? $plist['CFBundleVersion'] : 'CFBundleVersion';
            $info['MinimumOSVersion'] = array_key_exists('MinimumOSVersion', $plist) ? $plist['MinimumOSVersion'] : 'MinimumOSVersion';
            $info['DTPlatformVersion'] = array_key_exists('CFBundleIconFiles', $plist) ? $plist['DTPlatformVersion'] : 'DTPlatformVersion';
            $info['CFBundleIdentifier'] = array_key_exists('CFBundleIdentifier', $plist) ? $plist['CFBundleIdentifier'] : 'CFBundleIdentifier';
            $info['CFBundleDisplayName'] = array_key_exists('CFBundleDisplayName', $plist) ? $plist['CFBundleDisplayName'] : 'CFBundleDisplayName';
            $info['CFBundleShortVersionString'] = array_key_exists('CFBundleShortVersionString', $plist) ? $plist['CFBundleShortVersionString'] : 'CFBundleShortVersionString';
            $info['CFBundleShortVersionString'] = array_key_exists('CFBundleShortVersionString', $plist) ? $plist['CFBundleShortVersionString'] : 'CFBundleShortVersionString';
            $info['CFBundleSupportedPlatforms'] = implode(',', $plist['CFBundleSupportedPlatforms']);
            $info['APP_SERVER'] = array_key_exists('APP_SERVER', $plist) ? $plist['APP_SERVER'] : 'APP_SERVER';
            if (array_key_exists('UISupportedInterfaceOrientations', $plist)) {
                $info['UISupportedInterfaceOrientations'] = implode(',', $plist['UISupportedInterfaceOrientations']);
            }
        }

        $this->info = $info;
    }

    private function getIconPath($groups)
    {
        $iconsGroups = [];
        foreach ($groups as $group) {
            if (array_key_exists('CFBundlePrimaryIcon', $group)) {
                $group = $group['CFBundlePrimaryIcon'];
                if (array_key_exists('CFBundleIconFiles', $group)) {
                    $group = $group['CFBundleIconFiles'];
                    $iconsGroups[] = $group;
                }
            }
        }

        $iconPath = null;
        $original = call_user_func_array('array_merge', $iconsGroups);
        $original = array_unique($original);
        $largest = 0;
        foreach ($original as $maybe) {
            $maybepaths = $this->iconToPaths($maybe);
            foreach ($maybepaths as $maybepath) {
                if (file_exists($maybepath)) {
                    $size = filesize($maybepath);
                    if ($size > $largest) {
                        $iconPath = $maybepath;
                        $largest = $size;
                    }
                }
            }
        }

        return  $iconPath;
    }

    private function iconToPath($icon, $ext = '.png')
    {
        return $this->basePath . '/' . $icon . (strpos($icon, '.png') > 0 ? '' : $ext);
    }

    private function iconToPaths($icon)
    {
        return [
            $this->iconToPath($icon),
            $this->iconToPath($icon, '@2x.png'),
            $this->iconToPath($icon, '@3x.png')
        ];
    }

    public function unpackImage($path)
    {
        if (mime_content_type($path) == 'image/png') {
            //sometime images not compressed
            return $path;
        }

        if (PHP_OS == 'Darwin') {
            $pngdefry = 'pngdefry-osx';
        } else {
            //}else if(PHP_OS=='Linux'){
            $pngdefry = 'pngdefry-linux';
        }

        $pipes = [];

        $suffix = microtime(true);

        $fileinfo = pathinfo($path);

        $command = __DIR__ . '/' . $pngdefry . ' -s ' . $suffix . ' -o ' . $this->tmpDir . ' ' . str_replace(' ', '\\ ', $path);
        $process = proc_open($command, [], $pipes);

        if (is_resource($process)) {
            proc_close($process);
        }

        return $this->tmpDir . '/' . $fileinfo['filename'] . $suffix . '.' . $fileinfo['extension'];
    }

    public function __destruct()
    {
        if ($this->tmpDir) {
            $this->deleteDir($this->tmpDir);
        }
    }

    private function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new \InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}
