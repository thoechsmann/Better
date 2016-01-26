<?

class Backing  {

    const EIBTypeSwitch = 0;
    const EIBTypeScale = 1;

    private $displayId;
    private $getterId;
    private $setterId;
    private $eibType;

    public function __construct($module, $displayId, $getterId, $setterId, $eibType) {
        if($displayId == 0 || $getterId == 0 || $setterId == 0)
            throw new Exception("Backing::__construct - Some ids are 0.");

        $this->module = $module;
        $this->displayId = $displayId;
        $this->getterId = $getterId;
        $this->setterId = $setterId;
        $this->eibType = $eibType;
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
        }
    }

    public function GetValue()
    {
        return GetValue($this->getterId);
    }

    public function RegisterTrigger($additionalCode)
    {
        $triggerIdent = $this->getterId . "Trigger";
        $script = 'SetValue(' . $this->setterId . ', $_IPS[\'VALUE\']); ' . $additionalCode;
        $this->RegisterTrigger($triggerIdent, $this->getterId, $script, self::TriggerTypeUpdate);
    }

}

?>