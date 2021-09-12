<?php

namespace Mohamedhk2\PhpPatcher;

final class Patcher
{
    const STATUS_CAN_MODIFIED = 104;
    const STATUS_FILE_NOT_FOUND = 101;
    const STATUS_MODIFIED = 102;
    const STATUS_NOT_SUPPORTED = 103;
    const STATUS_SUCCESSFUL = 100;
    const TYPE_AFTER = 2;
    const TYPE_BEFORE = 3;
    const TYPE_REPLACE = 1;
    public static $status = 0;
    protected $check;
    protected $file_path;
    protected $replace;
    protected $search;
    protected $type = self::TYPE_REPLACE;

    /**
     * @param $file_path
     */
    public function __construct($file_path)
    {
        $this->file_path = $file_path;
    }

    /**
     * @return bool
     */
    public function canModified()
    {
        if (!$this->fileExist()) return false;
        return $this->preg_match();
    }

    /**
     * @return bool
     */
    public function fileExist()
    {
        return file_exists($this->file_path) && is_file($this->file_path);
    }

    /**
     * @param array $matches
     *
     * @return bool
     */
    protected function preg_match(&$matches = [])
    {
        return !!preg_match($this->search, $this->file_content(), $matches);
    }

    /**
     * @return false|string
     */
    protected function file_content()
    {
        return $this->fileExist() ? file_get_contents($this->file_path) : false;
    }

    /**
     * @param null $backup_full_path
     *
     * @return array|false|string|string[]
     * @throws \Exception
     */
    public function makeChange($backup_full_path = null)
    {
        if (!$this->fileExist()) {
            self::$status = self::STATUS_FILE_NOT_FOUND;
            return false;
        }
        if (!$this->preg_match($matches)) {
            self::$status = self::STATUS_NOT_SUPPORTED;
            return false;
        }
        if ($this->isModified()) {
            self::$status = self::STATUS_MODIFIED;
            return false;
        }
        $search = $matches[0];
        $eol = PHP_EOL;
        switch ($this->type) {
            case self::TYPE_REPLACE:
                $output = $this->replace;
                break;
            case self::TYPE_AFTER :
                $output = "{$search}{$eol}{$this->replace}{$eol}";
                break;
            case self::TYPE_BEFORE :
                $output = "{$eol}{$this->replace}{$eol}{$search}";
                break;
            default:
                throw new \Exception('Invalid type');
                break;
        }
        $new_content = str_replace($search, $output, $content = $this->file_content());
        self::$status = self::STATUS_SUCCESSFUL;
        if ($backup_full_path && !is_file($backup_full_path) && !is_dir($backup_full_path)) file_put_contents($backup_full_path, $content);
        return $new_content;
    }

    /**
     * @return bool
     */
    public function isModified()
    {
        if (empty($this->check) || !$this->fileExist()) return false;
        return !!preg_match($this->check, $this->file_content(), $matches);
    }

    /**
     * @param string $search
     *
     * @return $this
     * @throws \Exception
     */
    public function setAfter(string $search)
    {
        $this->set_replace($search, self::TYPE_AFTER);
        return $this;
    }

    /**
     * @param string $replace
     * @param $type
     *
     * @throws \Exception
     */
    protected function set_replace(string $replace, $type)
    {
        switch ($type) {
            case self::TYPE_REPLACE:
            case self::TYPE_AFTER :
            case self::TYPE_BEFORE :
                $this->replace = $replace;
                $this->type = $type;
                self::$status = 0;
                break;
            default:
                throw new \Exception('Invalid type');
                break;
        }
    }

    /**
     * @param string $search
     *
     * @return $this
     * @throws \Exception
     */
    public function setReplace(string $search)
    {
        $this->set_replace($search, self::TYPE_REPLACE);
        return $this;
    }

    /**
     * @param string $search
     *
     * @return $this
     * @throws \Exception
     */
    public function setBefore(string $search)
    {
        $this->set_replace($search, self::TYPE_BEFORE);
        return $this;
    }

    /**
     * @param string $check
     *
     * @return self
     */
    public function setCheck(string $check)
    {
        $this->check = $check;
        return $this;
    }

    /**
     * @param string $search
     *
     * @return self
     */
    public function setSearch(string $search)
    {
        $this->search = $search;
        self::$status = 0;
        return $this;
    }
}