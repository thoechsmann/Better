<?

class Backing  {

    const EIBTypeSwitch = 0;
    const EIBTypeScale = 1;

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
        $triggerIdent = $this->displayIdent . "Trigger";
        $displayId = $this->module->GetIDForIdent($this->displayIdent);
        $script = 'SetValue(' . $displayId . ', $_IPS[\'VALUE\']); ' . $additionalCode;
        $this->RegisterTrigger($triggerIdent, $this->getterId, $script, self::TriggerTypeUpdate);
    }

}

?>