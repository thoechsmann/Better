<?

require_once(__DIR__ . "/IPSObject.php");

class IPSLink extends IPSObject
{
    /// Parameter order changed!
    public function Register($name, $targetId, $position = 0) 
    {        
        $id = $this->_Register($name, $position);
            
        IPS_SetLinkTargetID($id, $tarketId);
        
        return $id;        
    }

    public function SetTargetId($targetId)
    {
        IPS_SetLinkTargetID($this->Id(), $targetId);
    }

    protected function CreateObject()
    {
        return IPS_CreateLink(0);
    }

    protected function DeleteObject($id)
    {
        IPS_DeleteLink($id);
    }

}

?>