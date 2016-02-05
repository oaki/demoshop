<?php

class MemcachedSession extends SessionHandler
{

    public function read($session_id)
    {
        return (string)parent::read($session_id);
    }
}

$memcacheSessionSaveHandler = new MemcachedSession();
session_set_save_handler($memcacheSessionSaveHandler, true);
