<?php
class ToolDemo extends CliBase{

    public function init() {
        $this->className = __CLASS__;
    }
    public function execute() {
        $this->lock();

        while(true) {
            echo 'running ' . time() . PHP_EOL;
            sleep(5);
        }
        $this->unlock();
    }
}
