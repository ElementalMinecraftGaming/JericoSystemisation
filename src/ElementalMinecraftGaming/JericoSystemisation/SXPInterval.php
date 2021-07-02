<?php

namespace ElementalMinecraftGaming\JericoSystemisation;

use pocketmine\scheduler\Task;
use ElementalMinecraftGaming\JericoSystemisation\Main;

class SXPInterval extends Task {
    
    public $plugin;
	
	public function __construct(Main $pg) {
		$this->plugin = $pg;
	}

    public function onRun(int $currentTick){
        $this->plugin->autoSXP();
    }
}