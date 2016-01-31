<?
require_once(__DIR__ . "/BetterBase.php");

class Backing  {

    const EIBTypeSwitch = 0;
    const EIBTypeScale = 1;
    const EIBTypeRGB = 2;

    private $displayIdent;
    private $getterId;
    private $setterId;
    private $eibType;

    public function __construct($module, $displayIdent, $getterId, $setterId, $eibType) {
        if($displayIdent == "" || $getterId == 0 || $setterId == 0)
            throw new Exception("Backing::__construct - Some ids are 0.");

        $this->module = $module;
        $this->displayIdent = $displayIdent;
        $this->getterId = $getterId;
        $this->setterId = $setterId;
        $this->eibType = $eibType;
    }

    public function DisplayIdent()
    {
        return $this->displayIdent;
    }

    public function SetValue($value)
    {
        $parent = IPS_GetParent($this->setterId);

        switch($this->eibType)
        {
            case self::EIBTypeSwitch:
                EIB_Switch($parent, $value);
                break;

            case self::EIBTypeScale:
                EIB_Scale($parent, $value);
                break;

            case self::EIBTypeRGB:
                $hex = $this->int2hex($value);
                $rgb = $this->hex2rgb($hex);
                EIB_RGB($parent, $rgb[0], $rgb[1], $rgb[2]);
                break;
        }
    }

    public function GetValue()
    {
        return GetValue($this->getterId);
    }

    public function RegisterTrigger($additionalCode)
    {
        $triggerIdent = $this->displayIdent . "Trigger";
        $displayId = $this->module->GetIDForIdent($this->displayIdent);
        $script = 'SetValue(' . $displayId . ', $_IPS[\'VALUE\']); ' . $additionalCode;
        $this->module->RegisterTrigger($triggerIdent, $this->getterId, $script, BetterBase::TriggerTypeUpdate);
    }

    private function int2hex($value)
    {
        return sprintf("%06X", $value);
    }

    private function hex2rgb($hex) 
    {         
        $rgb = array(); 
        $rgb[0] = hexdec ( $hex[0] . $hex[1] ); 
        $rgb[1] = hexdec ( $hex[2] . $hex[3] ); 
        $rgb[2] = hexdec ( $hex[4] . $hex[5] ); 
        return $rgb; 
    } 

}

?>