<?
require_once(__DIR__ . "/../BetterBase.php");

require_once(__DIR__ . "/../IPS/IPS.php");

class SceneArray {
    private $size;
    private $module;

    public function __construct($module, $size)
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

    public function At($index)
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
    private $index;
    private $module;

    const StrScene = "Scene";

    public function __construct($module, $index) {
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
        return new IPSScript($this->module, self::StrScene . $this->index . "Alexa");
    }

    public function RegisterProperties()
    {
        $this->NameProp()->Register();
        $this->ColorProp()->Register();
        $this->CreateAlexaScript();
    }

    public function RegisterAlexaScript()
    {
        $this->NameProp()->Register();
        $this->ColorProp()->Register();

        $this->AlexaScript()->Register("Alexa",
            ' <?
                if($_IPS[\'SENDER\'] == "AlexaSmartHome") {
                    IPS_LogMessage("AlexaScript", "test1");
                    BL_SetScene('$this->module','$this->$index');
                }
            ?>'
        );
        $this->AlexaScript()->Hide();
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