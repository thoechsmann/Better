<?

abstract class IPSObject  {
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

    public function SetName($name)
    {
        IPS_SetName($this->Id(), $name);
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
        // IPS_LogMessage(__CLASS__, "Registering - " . $this);

        $hide = false;
        if($name == "")
        {
            $name = $this->Ident();
            $hide = true;
        }

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

        $this->SetName($name);
        $this->SetPosition($position);        
        $this->SetHidden($hide);

        return $id;            
    }

    protected function IsCorrectObjectType($id)
    {
        return false;        
    }

    // Used e.g. by variables to not delete them if same type. Not sure if there is some benefit in not deleting them always.
    abstract protected function CreateObject();
    abstract protected function DeleteObject($id);

}

?>