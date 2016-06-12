<?
require_once(__DIR__ . "/IPSObject.php");

class IPSVarNew extends IPSObjectNew
{
    public function GetValue()
    {        
        return GetValue($this->Id());
    }

    public function SetValue($value)
    {
        $this->CheckValue($value);
        SetValue($this->Id(), $value);
    }

    public function SetProfile($profile)
    {
        IPS_SetVariableCustomProfile($this->Id(), $profile);
    }

    public function EnableAction() {
        IPS_EnableAction($this->parentId, $this->Ident());
    }
    
    public function DisableAction() {
        IPS_DisableAction($this->parentId, $this->Ident());
    }

    public function Register($name = "", $profile = "", $position = 0) 
    {
        $id = $this->_Register($name, $position);

        if($profile != "") {
            if(!IPS_VariableProfileExists($profile)) {
                throw new Exception("Profile with name ".$profile." does not exist");
            }
        }

        IPS_SetVariableCustomProfile($id, $profile);
        
        return $id;            
    }

    public function __toString()
    {
        return parent::__toString() . " type: " . static::GetVarTypeName();
    }

    public function CheckValue($value)
    {
        if(!static::ValueValid($value))
        {
            throw new Exception("IPSVar - SetValue", "value: $value is not of type " . static::GetVarTypeName() . " - " . $this);
        }
    }

    protected function CreateObject()
    {
        return IPS_CreateVariable(static::GetVarTypeId());
    }

    protected function DeleteObject($id)
    {
        IPS_DeleteVariable($id);
    }

    protected function IsCorrectObjectType($id)
    {
        if(!IPS_VariableExists($id))
            throw new Exception("Ident with name ".$this->Ident()." is used for wrong object type");
            
        return IPS_GetVariable($id)["VariableType"] == static::GetObjectType();
    }

    protected function GetVarTypeName()
    {
        throw new Exception("GetVarTypeName overide missing!");        
    }

    protected function GetVarTypeId()
    {
        throw new Exception("GetVarTypeId overide missing!");        
    }

    protected function ValueValid($value)
    {
        throw new Exception("ValueValid overide missing!");        
    }

}

class IPSVarBooleanNew extends IPSVarNew
{
    protected function GetVarTypeName()
    {
        return "Boolean";      
    }

    protected function GetVarTypeId() 
    {
        return 0;
    }

    protected function ValueValid($value)
    {
        return is_bool($value);
    }
}

class IPSVarIntegerNew extends IPSVarNew
{
    protected function GetVarTypeName()
    {
        return "Integer";      
    }

    protected function GetVarTypeId()
    {
        return 1;
    }

    protected function ValueValid($value)
    {
        return is_integer($value);
    }
}

class IPSVarFloatNew extends IPSVarNew
{
    protected function GetVarTypeName()
    {
        return "Float";      
    }

    protected function GetVarTypeId()
    {
        return 2;
    }

    protected function ValueValid($value)
    {
        return is_float($value);
    }
}

class IPSVarStringNew extends IPSVarNew
{
    protected function GetVarTypeName()
    {
        return "String";      
    }

    protected function GetVarTypeId()
    {
        return 3;
    }

    protected function ValueValid($value)
    {
        return is_string($value);
    }
}

?>