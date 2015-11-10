<?
require_once(__DIR__ . "/../BetterBase.php");

class BetterShutter extends BetterBase {

	public function Create() 
    {
		//Never delete this line!
		parent::Create();		

		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.
        $this->RegisterPropertyInteger("positionId", 0);
        $this->RegisterPropertyInteger("upDownId", 0);
        $this->RegisterPropertyInteger("stopId", 0);
        $this->RegisterPropertyInteger("windowId", 0);
	}
	
	public function ApplyChanges() 
    {
		//Never delete this line!
		parent::ApplyChanges();
		
        // Cleanup
        foreach(IPS_GetChildrenIDs($this->InstanceID) as $childId)
        {
            $this->DeleteObject($childId);
        }

        $this->RegisterLink("windowStatus", "Fenster", $this->ReadPropertyInteger("windowId"), 1);
	}

    public function RequestAction($Ident, $Value) 
    {    
        switch($Ident) {
            case "Boost":
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

}
?>