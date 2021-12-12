<?
require_once(__DIR__ . "/../BetterBase.php");

require_once(__DIR__ . "/../IPS/IPS.php");

class SceneArray {
    private int $size;
    private $module;

    public function __construct($module, int $size)
    {
        $this->module = $module;
        $this->size = $size;
    }

    public function Count()
    {
        $count = 0;
        
        for($i=0; $i<$this->size; $i++)
        {
            $light = $this->At($i);

            if(!$light->IsDefined())
            {
                return $count;
            }

            $count++;
        }

        return $count;
    }

    public function At(int $index)
    {
        return new Scene($this->module, $index);
    }

    public function RegisterProperties()
    {
        for($i=0; $i<$this->size; $i++)
        {
            $this->At($i)->RegisterProperties();
        }
    }

    public function RegisterAlexaScripts()
    {
        for($i=0; $i<$this->size; $i++)
        {
            $this->At($i)->RegisterAlexaScript();
        }
    }

}

class Scene
{
    private int $index;
    private $module;

    const StrScene = "Scene";

    public function __construct($module, int $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties
    private function NameProp()
    {        
        return new IPSPropertyString($this->module, self::StrScene . $this->index . "Name");
    }   

    private function ColorProp()
    {        
        return new IPSPropertyString($this->module, self::StrScene . $this->index . "Color");
    }

    // Scripts
    private function AlexaScript()
    {
        return new IPSScript($this->module->InstanceId(), self::StrScene . $this->index . "Alexa");
    }

    public function RegisterProperties()
    {
        $this->NameProp()->Register();
        $this->ColorProp()->Register();
    }

    public function RegisterAlexaScript()
    {
        if($this->IsDefined())
        {
            $this->AlexaScript()->Register("", "<? BL_SetScene(" . $this->module->InstanceId() . "," . $this->index . ", true); ?>");        
            $this->AlexaScript()->Hide();
        }
    }

    public function Name()
    {
        return $this->NameProp()->Value();
    }

    public function SetName($value)
    {
        $this->NameProp()->SetValue($value);
    }

    public function Color()
    {
        return intval($this->ColorProp()->Value(), 0);
    }

    public function SetColor($value)
    {
        $this->ColorProp()->SetValue($value);
    }

    public function IsDefined()
    {
        return $this->Name() != "";
    }
}

?>