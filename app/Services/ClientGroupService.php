<?php
namespace App\Services;

use App\Models\ClientGroup;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientGroupService
{
    protected $clientGroup;
    public function __construct(ClientGroup $clientGroup){
        $this->clientGroup = $clientGroup;
    }

    public function getAllClientGroup(){
        try {
            Log::info('Fetching all ClientGroup');
            return $this->clientGroup->get();
        } catch (Exception $e) {
            Log::error('Failed to fetch ClientGroup: ' . $e->getMessage());
            throw new Exception('Failed to fetch ClientGroup');
        }
    }

    public function createClientGroup(array $data)
    {
        try{
            Log::info('Creating new ClientGroup');
            $clientgroup = $this->clientGroup->create($data);
            DB::commit();
            return $clientgroup;
        }
        catch(Exception $e){
            DB::rollBack();
            Log::error('Failed to create ClientGroup: ' .$e->getMessage());
            throw new Exception('Failed to create ClientGroup');
        }
    }

    public function updateClientGroup(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            Log::info("Updating clientGroup with ID: $id");
            $clientgroup = $this->clientGroup->findOrFail($id);
            $clientgroup->update($data);
            DB::commit();
            return $clientgroup;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update clientGroup: ' . $e->getMessage());
            throw new Exception('Failed to update clientGroup');
        }
    }

    public function findOrFailClientGroup($id)
    {
        try {
            Log::info('Failed to find clientgroup');
            $clientgroup = $this->clientGroup->findOrFail($id);
            return $clientgroup;
        } catch (Exception $e) {

            Log::error('Failed to find clientgroup: ' . $e->getMessage());
            throw new Exception('Failed to find clientgroup');
        }
    }

    public function deleteClientGroup(int $id)
    {
        DB::beginTransaction();
        try {
            Log::info("Deleting clientgroup with ID: $id");
            $clientgroup = $this->clientGroup->findOrFail($id);
            $clientgroup->delete();
            DB::commit();
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('clientgroup not found: ' . $e->getMessage());
            throw new ModelNotFoundException('clientgroup not found');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete clientgroup: ' . $e->getMessage());
            throw new Exception('Failed to delete clientgroup');
        }
    }
}
