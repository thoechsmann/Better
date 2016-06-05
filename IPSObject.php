<?

class IPSObjectNew  {

    private $ident;
    private $id = false;
    protected $parentId;

    public function __construct($parentId, $ident) {
        $this->parentId = $parentId;
        $this->ident = $ident;
    }

    public function __toString()
    {
        return 
        "Ident: " . $this->Ident() .
        " ParentId: " . $this->parentId;
    }

    public function Ident()
    {
        return $this->ident;
    }

    public function Id()
    {
        if($this->id === false)
        {
            $this->id = $this->GetIDForIdent($this->ident);

            if($this->id === false)
            {
                throw new Exception("Variable::Id() - Ident " . $this->ident . " not found.");
            }
        }

        return $this->id;
    }

    protected function SetId($id)
    {
        if($this->id !== false)
        {
            $currentId = $this->id;
            throw new Exception("Variable::SetId($id) - Id already set to $currentId. Changing is not allowed.");
        }

        $this->id = $id;
    }

    public function GetIDForIdent($ident)
    {
        $id = @IPS_GetObjectIDByIdent($ident, $this->parentId); 
        
        if($id === false)
            $id = 0;
        
        return $id;
    }  

    public function SetHidden($hide)
    {
        IPS_SetHidden($this->Id(), $hide);
    }

    public function Hide()
    {
        $this->SetHidden(true);
    }

    public function Show()
    {
        $this->SetHidden(false);
    }

    public function SetPosition($pos)
    {
        IPS_SetPosition($this->Id(), $pos);
    }

    public function SetIcon($icon)
    {
        IPS_SetIcon($this->Id(), $icon);
    }

    protected function _Register($name, $position ) 
    {
        IPS_LogMessage("IPSObject", "Registering - " . $this);

        if($name == "")
            $name = $this->Ident();

        $id = $this->GetIDForIdent($this->Ident());

        if($id > 0) {            
            if(!static::IsCorrectObjectType($id)) {
                static::DeleteObject($id);
                $id = 0;
            }
        }
        
        if($id == 0)
        {
            $id = static::CreateObject();

            IPS_SetParent($id, $this->parentId);
            IPS_SetIdent($id, $this->Ident());            
        }

        IPS_SetName($id, $name);        
        IPS_SetPosition($id, $position);
        
        return $id;            
    }

    // Used e.g. by variables to not delete them if same type. Not sure if there is some benefit in not deleting them always.
    protected function CreateObject()
    {
        return false;
    }

    protected function DeleteObject($id)
    {
        throw new Exception("DeleteObject overide missing!");        
    }

    protected function IsCorrectObjectType($id)
    {
        throw new Exception("IsCorrectObjectType overide missing!");        
    }
}

class IPSVarNew extends IPSObjectNew
{
    public function GetValue()
    {        
        return GetValue($this->Id());
    }

    public function SetValue($value)
    {
        $this->CheckType($value);
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
        return parent::__toString() . " type: " . static::GetTypeName();
    }

    public function CheckValue($value)
    {
        if(!static::ValueValid($value))
        {
            throw new Exception("IPSVar - SetValue", "value: $value is not of type " . static::GetTypeName() . " - " . $this);
        }
    }

    protected function CreateObject()
    {
        IPS_CreateVariable(static::GetTypeId());
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

    protected function ValueValid()
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

    protected function ValueValid()
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

    protected function ValueValid()
    {
        return is_integer($value);
    }
}

?>