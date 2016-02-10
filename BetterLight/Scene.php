<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");

class Scene
{
    private $index;
    private $module;

    const Size = 4;
    const StrScene = "Scene";

    public function __construct($module, $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties

    private function NameProp()
    {        
        return new PropertyString($this->module, self::StrScene . $this->index . "Name");
    }   

    private function ColorProp()
    {        
        return new PropertyString($this->module, self::StrScene . $this->index . "Color");
    }

    public function RegisterProperties()
    {
        $this->NameProp()->Register();
        $this->ColorProp()->Register();
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