<?

require_once(__DIR__ . "/IPSObject.php");

class IPSLink extends IPSObject
{
    /// Parameter order changed!
    public function Register($name, $targetId, $position = 0) 
    {        
        $id = $this->_Register($name, $position);
            
        $this->SetTargetId($targetId);
        
        return $id;        
    }

    public function SetTargetId($targetId)
    {
        IPS_SetLinkTargetID($this->Id(), $targetId);
    }

    protected function CreateObject()
    {
        return IPS_CreateLink();
    }

    protected function DeleteObject($id)
    {
        IPS_DeleteLink($id);
    }

}

?>