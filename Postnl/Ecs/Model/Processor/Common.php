<?php
namespace Postnl\Ecs\Model\Processor;


class Common {

    protected $_server;
    protected $_home;

    public function __construct($args)
    {
        if ( ! is_array($args) || ! isset($args[0]) || ! $args[0] instanceof \Magento\Framework\Filesystem\Io\Sftp)
            throw new \Postnl\Ecs\Exception(__('Server instance is missing'));
        $this->_server = $args[0];
        $this->_home = $this->_server->pwd();
        if ( ! $this->_home)
            throw new \Postnl\Ecs\Exception(__('Can not fetch home directory'));
    }

    public function restorePath()
    {
        if ( ! $this->_server->cd($this->_home))
            throw new \Postnl\Ecs\Exception(__('Can not cd to home directory'));
    }

    public function isEnabled()
    {
        return false;
    }

    protected function _filterFiles($files, $filter)
    {
        $result = array();
        foreach ($files as $file)
        {
            if (preg_match($filter, $file['text']))
                $result[] = $file['text'];
        }

        sort($result);
        return $result;
    }

    protected function _getBadCharacters()
    {
        return array(
            ';',
            '\\',
            '`',
            '\'',
            '"',
            '&',
            '*',
            '{',
            '}',
            '[',
            ']',
            '!',
            '<',
            '>'

        );
    }

    protected function _cleanupString($string, $maxLength = 0)
    {
        $trimmed = trim(preg_replace('#\s+#us', ' ', str_replace($this->_getBadCharacters(), '', $string)));
        if ($maxLength && mb_strlen($string, 'UTF-8') > $maxLength)
            $trimmed = mb_substr($string, 0, $maxLength, 'UTF-8');
        return $trimmed;
    }

    protected function _getFloat($value, $precision = 2)
    {
        $value = (float) $value;
        return number_format(round($value, $precision), $precision, '.', '');
    }

}
