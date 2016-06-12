<?
require_once(__DIR__ . "/IPSObject.php");

class IPSScriptNew extends IPSObjectNew
{
    public function Register($name, $content = "<?\n?>", $position = 0)    
    {        
        $this->_Register($name, $position);
            
        $this->SetContent($content);
        
        return $this->Id();        
    }

    public function SetContent($content)
    {
        IPS_SetScriptContent($this->Id(), $content);
    }

    protected function CreateObject()
    {
        IPS_CreateScript(0);
    }

    protected function DeleteObject($id)
    {
        IPS_DeleteScript($id);
    }

    protected function IsCorrectObjectType($id)
    {
        if(!IPS_VariableExists($id))
            throw new Exception("Ident with name ".$this->Ident()." is used for wrong object type");
            
        return true;
    }
}

?>