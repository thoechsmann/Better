<?
declare(strict_types=1);
require_once(__DIR__ . "/IPSObject.php");

abstract class IPSVar extends IPSObject
{
    public function Register(string $name = "", string $profile = "", int $position = 0)
    {
        $this->_Register($name, $position);

        if($profile != "") {
            if(!IPS_VariableProfileExists($profile)) {
                throw new Exception("Profile with name ".$profile." does not exist");
            }
        }

        $this->SetProfile($profile);

        return $this->Id();
    }

    public function Value()
    {
        return GetValue($this->Id());
    }

    public function SetValue($value)
    {
        $this->CheckValue($value);
        SetValue($this->Id(), $value);
    }

    public function SetProfile(string $profile)
    {
        IPS_SetVariableCustomProfile($this->Id(), $profile);
    }

    public function __toString()
    {
        return parent::__toString() . " type: " . $this->GetVarTypeName();
    }

    public function CheckValue($value)
    {
        if(!$this->ValueValid($value))
        {
            throw new Exception("IPSVar - SetValue value: $value is not of type " . $this->GetVarTypeName() . " - " . $this);
        }
    }

    protected function CreateObject()
    {
        return IPS_CreateVariable($this->GetVarTypeId());
    }

    protected function DeleteObject($id)
    {
        IPS_DeleteVariable($id);
    }

    protected function IsCorrectObjectType(int $id)
    {
        if(!IPS_VariableExists($id))
            throw new Exception("Ident with name ".$this->Ident()." is used for wrong object type");

        return IPS_GetVariable($id)["VariableType"] == $this->GetVarTypeId();
    }

    abstract protected function GetVarTypeName();
    abstract protected function GetVarTypeId();
    protected function ValueValid($value)
    {
        return true;
    }
}

class IPSVarBoolean extends IPSVar
{
    protected function GetVarTypeName()
    {
        return "Boolean";
    }

    protected function GetVarTypeId()
    {
        return 0;
    }

    // protected function ValueValid($value)
    // {
    //     return is_bool($value);
    // }
}

class IPSVarInteger extends IPSVar
{
    protected function GetVarTypeName()
    {
        return "Integer";
    }

    protected function GetVarTypeId()
    {
        return 1;
    }

    // protected function ValueValid($value)
    // {
    //     return is_int($value) || ctype_digit($value);
    // }
}

class IPSVarFloatNew extends IPSVar
{
    protected function GetVarTypeName()
    {
        return "Float";
    }

    protected function GetVarTypeId()
    {
        return 2;
    }

    // protected function ValueValid($value)
    // {
    //     return $value == (string)(float)$value;
    // }
}

class IPSVarString extends IPSVar
{
    protected function GetVarTypeName()
    {
        return "String";
    }

    protected function GetVarTypeId()
    {
        return 3;
    }

    // protected function ValueValid($value)
    // {
    //     return true;
    // }
}

?>
