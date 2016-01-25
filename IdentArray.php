<?
class Variable  {

    protected $ident;
    protected $module;

    public function __construct($module, $ident) {
        $this->module = $module;
        $this->ident = $ident;
    }

    public function RegisterVariableInteger($name, $profile, $position = 0)
    {
        $module->RegisterVariableInteger($this->ident, $name, $profile, $position);
    }

    public function Ident()
    {
        return $this->ident;
    }

    public function Id()
    {
        return $module->GetIDForIdent($this->ident);
    }

    public function SetHidden($hide)
    {
        IPS_SetHidden($this->Id(), $hide);
    }

    public function EnableAction()
    {
        $module->EnableAction($this->ident);
    }

    public function GetValue()
    {        
        return GetValue($this->Id());
    }

    public function SetValue($value)
    {
        SetValue($this->Id(), $value);
    }

    public function SetPosition($pos)
    {
        IPS_SetPosition($this->Id(), $pos);
    }
}



?>