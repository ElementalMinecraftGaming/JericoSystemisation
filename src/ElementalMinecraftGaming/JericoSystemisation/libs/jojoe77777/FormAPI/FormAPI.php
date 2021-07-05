<?php

declare(strict_types = 1);

namespace ElementalMinecraftGaming\JericoSystemisation\libs\jojoe77777\FormAPI;

use pocketmine\plugin\PluginBase;

class FormAPI extends PluginBase{

    /**
     * @deprecated
     *
     * @param callable $function
     * @return CustomForm
     */
    public function createCustomForm(callable $function = null) : CustomForm {
        return new CustomForm($function);
    }

    /**
     * @deprecated
     *
     * @param callable|null $function
     * @return SimpleForm
     */
    public function createSimpleForm(callable $function = null) : SimpleForm {
        return new SimpleForm($function);
    }
}
