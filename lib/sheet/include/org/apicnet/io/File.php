<?php

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

/**
* @version 2.0
* @author Nicolas BUI <nbui@wanadoo.fr>
*
* This source file is part of JPHP Library Project.
* Copyright: 2002 Vitry sur Seine/FRANCE
*
* The latest version can be obtained from:
* http://www.jphplib.org
*/
APIC::import('org.apicnet.util.StringBuffer');

class File extends ErrorManager
{
    public $path = null;
    public $separator = null;
    public $os = null;
    
    public function __construct($path = null, $new = false)
    {
        $this->separator = DIRECTORY_SEPARATOR;
        $this->os = getenv('OS');
        $this->setFilePath($path);
        if ($new) {
            $this->createFile();
        }
        parent::__construct();
    }
    
    public function setFilePath($path)
    {
        if (File::validClass($path)) {
            $this->path = $path->getFilePath();
        } else {
            $this->path = StringBuffer::validClass($path) ? $path->toString() : $path;
        }
    }
    
    public function getFilePath()
    {
        return $this->path;
    }
    
    public function getFileName()
    {
        return basename($this->getFilePath());
    }
    
    public function getParentDirectory()
    {
        return dirname($this->getFilePath());
    }
    
    public function getRealPath()
    {
        return realpath($this->getFilePath());
    }
    
    public function exists()
    {
        return file_exists($this->getFilePath());
    }
    
    public function isDirectory()
    {
        return is_dir($this->getFilePath());
    }
    
    public function isFile()
    {
        return is_file($this->getFilePath());
    }
    
    public function createFile()
    {
        $isSucceful = true;
        if (!$handle = fopen($this->getFilePath(), 'w')) {
            $isSucceful = false;
        }
        fclose($handle);

        return $isSucceful;
    }
    
    public function delFile()
    {
        return unlink($this->getFilePath());
    }
    
    
    public function writeData($data)
    {
        $isSucceful = true;
        // Assurons nous que le fichier est accessible en écriture
        if ($this->isWriteable()) {
            if (!$handle = fopen($this->getFilePath(), 'w')) {
                $isSucceful = false;
                exit;
            }
            // Write $somecontent to our opened file.
            if (!fwrite($handle, $data)) {
                $isSucceful = false;
                exit;
            }
            fclose($handle);
        }

        return $isSucceful;
    }
    
    public function readData()
    {
        $isSucceful = true;
        // Assurons nous que le fichier est accessible en écriture
        if ($this->isReadable()) {
            if (!$handle = fopen($this->getFilePath(), 'r')) {
                $isSucceful = false;
                exit;
            }
            $contents = fread($handle, filesize($this->getFilePath()));
            fclose($handle);
        }
        if (!$isSucceful) {
            return $isSucceful;
        }

        return $contents;
    }
    
    public function lists($filenameFilter = null)
    {
        $path = StringBuffer::toStringBuffer($this->getFilePath());
        if (!isset($path)) {
            return [];
        }
        $filter = FilenameFilter::validClass($filenameFilter) ? $filenameFilter : new FilenameFilter();
        $files = [];
        $folders = [];
        if ($this->exists() && $this->isDirectory()) {
            $path = $path->replace('/', DIRECTORY_SEPARATOR);
            if (!$path->endsWith(DIRECTORY_SEPARATOR)) {
                $path->append(DIRECTORY_SEPARATOR);
            }
            $handle = opendir($path->toString());
            while ($file = readdir($handle)) {
                $filename = new StringBuffer($file);
                if (!$filename->equals('.') && !$filename->equals('..')) {
                    $filename->prepend($path);
                    $validfile = new File($filename);
                    if ($filter->accept($validfile, $validfile->getParentDirectory())) {
                        if ($validfile->isFile()) {
                            $files[] = $filename->toString();
                        } elseif ($validfile->isDirectory()) {
                            $folders[] = $filename->toString();
                        }
                    }
                }
            }
            closedir($handle);
        }

        return array_merge($folders, $files);
    }
    
    public function listFiles($filenameFilter = null)
    {
        $path = StringBuffer::toStringBuffer($this->getFilePath());
        $s = null;
        if (!isset($path)) {
            return [];
        }
        $filter = isset($filenameFilter) ? $filenameFilter : new FilenameFilter();
        $files = [];
        $folders = [];
        if ($this->exists() && $this->isDirectory()) {
            $path = $path->replace('/', DIRECTORY_SEPARATOR);
            if (!$path->endsWith(DIRECTORY_SEPARATOR)) {
                $path->append(DIRECTORY_SEPARATOR);
            }
            $handle = opendir($path->toString());
            while ($file = readdir($handle)) {
                $filename = new StringBuffer($file);
                if (!$filename->equals('.') && !$filename->equals('..')) {
                    $filename->prepend($path);
                    $validfile = new File($filename);
                    if ($filter->accept($validfile, $validfile->getParentDirectory())) {
                        if ($validfile->isFile()) {
                            $files[] = $validfile;
                        } elseif ($validfile->isDirectory()) {
                            $folders[] = $validfile;
                        }
                    }
                }
            }
            closedir($handle);
        }

        return array_merge($folders, $files);
    }
    
    public function length()
    {
        if (!$this->isFile()) {
            return 0;
        }

        return filesize($this->getFilePath());
    }
    
    public function lastModified()
    {
        return filemtime($this->getFilePath());
    }
    
    public function lastAccessed()
    {
        return fileatime($this->getFilePath());
    }
    
    public function setModification($time = null)
    {
        if (!$this->isFile()) {
            return;
        }
        touch($this->getFilePath(), $time);
    }
    
    public function isReadable()
    {
        if (!$this->isFile()) {
            return false;
        }

        return is_readable($this->getFilePath());
    }
    
    public function isWriteable()
    {
        if (!$this->isFile()) {
            return false;
        }

        return is_writeable($this->getFilePath());
    }
    
    public function toString()
    {
        return $this->getFilePath();
    }
    
    public function copyTo($file, $only_if_inexist = false)
    {
        if (File::validClass($file) && $this->exists()) {
            if ($only_if_inexist == true) {
                if ($file->exists() == false) {
                    return copy($this->getFilePath(), $file->getFilePath());
                }
            } else {
                return copy($this->getFilePath(), $file->getFilePath());
            }
        }

        return false;
    }
    
    public function moveTo($file, $only_if_inexist = false)
    {
        if (File::validClass($file) && $this->exists()) {
            if ($only_if_inexist == true) {
                if ($file->exists() == false) {
                    return rename($this->getFilePath(), $file->getFilePath());
                }
            } else {
                return rename($this->getFilePath(), $file->getFilePath());
            }
        }

        return false;
    }
    
    public function mkdirs($perms)
    {
        $path = StringBuffer::toStringBuffer($this->getFilePath());
        if ($path->startsWith(".\\") || $path->startsWith("./")) {
            $path = $path->substring(2);
            $path = $path->toString();
        } else {
            $path = $this->toString();
        }
        $struct = preg_split('!\\/+!xsmi', $path, -1, PREG_SPLIT_NO_EMPTY);
        $part = '';
        $len = count($struct);
        for ($i = 0; $i < $len; $i++) {
            $part .= $struct[$i];
            @mkdir($part, $perms);
            $part .= '/';
        }

        return $this->exists();
    }
    
    public function validClass($object)
    {
        return APICObject::validClass($object, 'file');
    }
}
