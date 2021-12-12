<?
declare(strict_types=1);
require_once(__DIR__ . "/IPSObject.php");

class IPSLink extends IPSObject
{
    /// Parameter order changed!
    public function Register(string $name, int $targetId, int $position = 0) 
    {        
        $id = $this->_Register($name, $position);
            
        $this->SetTargetId($targetId);
        
        return $id;        
    }

    public function SetTargetId(int $targetId)
    {
        IPS_SetLinkTargetID($this->Id(), $targetId);
    }

    protected function CreateObject()
    {
        return IPS_CreateLink();
    }

    protected function DeleteObject(int $id)
    {
        IPS_DeleteLink($id);
    }

}

?>