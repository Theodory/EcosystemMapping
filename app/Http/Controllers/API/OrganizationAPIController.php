<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateOrganizationAPIRequest;
use App\Http\Requests\API\UpdateOrganizationAPIRequest;
use App\Ecosystem\Models\Organization;
use App\Ecosystem\Models\Location;
use App\Ecosystem\Models\Ecosystem;
use App\Ecosystem\Models\Project;
use App\Ecosystem\Models\Event;
use App\Ecosystem\Repositories\OrganizationRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use League\Fractal;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use App\Ecosystem\Transformer\OrganizationTransformer;
use Response;
use DB;
use Input;

/**
 * Class OrganizationController
 * @package App\Http\Controllers\API
 */

class OrganizationAPIController extends AppBaseController
{
    /** @var  OrganizationRepository */
    private $organizationRepository;

    public function __construct(OrganizationRepository $organizationRepo)
    {
        $this->organizationRepository = $organizationRepo;
    }

    /**
     * Display a listing of the Organization.
     * GET|HEAD /organizations
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->organizationRepository->pushCriteria(new RequestCriteria($request));
        $this->organizationRepository->pushCriteria(new LimitOffsetCriteria($request));
        $organizations = $this->organizationRepository->all();

        $fractal    = new Manager();
        $resource   = new Collection($organizations, new OrganizationTransformer);
        $data_to_array = $fractal->createData($resource)->toArray();

        return $this->sendResponse($data_to_array, 'Organizations retrieved successfully');
    }

    /**
     * Store a newly created Organization in storage.
     * POST /organizations
     *
     * @param CreateOrganizationAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateOrganizationAPIRequest $request)
    {
        $input = $request->all();

        DB::beginTransaction();

        try {
            $location = Location::create([
              'address' => Input::get('address'),
              'long' => Input::get('long'),
              'lat'  => Input::get('lat')
            ]);

            $organization = $this->organizationRepository->create([
              'name' => Input::get('name'),
              'website'=> Input::get('website'),
              'target_group' => Input::get('target_group'),
              'description' => Input::get('description'),
              'date_founded' => Input::get('date_founded'),
              'date_registered' => Input::get('date_registered'),
              'tin_number' => Input::get('tin_number')
            ]);

            $organization->roles()->attach(Input::get('role_id'));

            $organization->sectors()->attach(Input::get('sector_id'), ['description' => Input::get('sector_description')]);

            $organization->locations()->attach($location->id);

            $ecosystem = Ecosystem::find(Input::get('ecosystem_id'));

            $ecosystem->organizations()->attach($organization->id, ['status' => 'active']);

            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong

            return response()->error($e->errorInfo[2], 500);
        }

        return $this->sendResponse($organization->toArray(), 'Organization saved successfully');
    }

    /**
     * Display the specified Organization.
     * GET|HEAD /organizations/{id}
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var Organization $organization */
        $organization = $this->organizationRepository->findWithoutFail($id);

        if (empty($organization)) {
            return $this->sendError('Organization not found');
        }
        $fractal    = new Manager();
        $resource   = new Item($organization, new OrganizationTransformer);
        $data_to_array = $fractal->createData($resource)->toArray();

        return $this->sendResponse($data_to_array, 'Organization retrieved successfully');
    }

    /**
     * Update the specified Organization in storage.
     * PUT/PATCH /organizations/{id}
     *
     * @param  int $id
     * @param UpdateOrganizationAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateOrganizationAPIRequest $request)
    {
        $input = $request->all();

        /** @var Organization $organization */
        $organization = $this->organizationRepository->findWithoutFail($id);

        if (empty($organization)) {
            return $this->sendError('Organization not found');
        }

        $organization = $this->organizationRepository->update($input, $id);

        return $this->sendResponse($organization->toArray(), 'Organization updated successfully');
    }

    /**
     * Remove the specified Organization from storage.
     * DELETE /organizations/{id}
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var Organization $organization */
        $organization = $this->organizationRepository->findWithoutFail($id);

        if (empty($organization)) {
            return $this->sendError('Organization not found');
        }

        $organization->delete();

        return $this->sendResponse($id, 'Organization deleted successfully');
    }

    public function locations($id)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $locations = $organization->locations()->get();
      return $this->sendResponse($locations, 'Organisation locations retrieved successfully');
    }

    public function roles($id)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $roles = $organization->roles()->get();
      return $this->sendResponse($roles, 'Organisation roles retrieved successfully');
    }

    public function contacts($id)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $contacts = $organization->contacts()->get();
      return $this->sendResponse($contacts, 'Organisation contacts retrieved successfully');
    }

    public function sectors($id)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $sectors = $organization->sectors()->get();
      return $this->sendResponse($sectors, 'Organisation sectors retrieved successfully');
    }

    public function stages($id)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $stages = $organization->stages()->get();
      return $this->sendResponse($stages, 'Organisation stages retrieved successfully');
    }


    public function projects($id)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $projects = $organization->projects()->get();
      return $this->sendResponse($projects, 'Organisation projects retrieved successfully');
    }


    public function events($id)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $events = $organization->events()->get();
      return $this->sendResponse($events, 'Organisation events retrieved successfully');
    }



    public function attachProjects($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $projectId = $input['project_id'];

      $organization->projects()->attach($projectId);

      return $this->sendResponse('success', 'Organisation projects attached successfully');
    }


    public function detachProjects($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $projectId = $input['project_id'];

      $organization->projects()->detach($projectId);

      return $this->sendResponse('success', 'Organisation projects detached successfully');
    }

    public function attachRoles($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $roleId = $input['role_id'];

      $organization->roles()->attach($roleId);

      return $this->sendResponse('success', 'Organisation roles attached successfully');
    }

    public function detachRoles($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $roleId = $input['role_id'];

      $organization->roles()->detach($roleId);

      return $this->sendResponse('success', 'Organisation roles detached successfully');
    }


    public function attachSectors($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $sectorId = $input['sector_id'];

      $organization->sectors()->attach($sectorId);

      return $this->sendResponse('success', 'Organisation sectors attached successfully');
    }

    public function detachSectors($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $sectorId = $input['sector_id'];

      $organization->sectors()->detach($sectorId);

      return $this->sendResponse('success', 'Organisation sectors detached successfully');
    }


    public function attachStages($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $stageId = $input['stage_id'];

      $organization->stages()->attach($stageId);

      return $this->sendResponse('success', 'Organisation stages attached successfully');
    }

    public function detachStages($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $stageId = $input['stage_id'];

      $organization->stages()->detach($stageId);

      return $this->sendResponse('success', 'Organisation stages detached successfully');
    }

    public function attachEvents($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $eventId = $input['event_id'];

      $organization->events()->attach($eventId);

      return $this->sendResponse('success', 'Organisation events attached successfully');
    }


    public function detachEvents($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $eventId = $input['event_id'];

      $organization->events()->detach($eventId);

      return $this->sendResponse('success', 'Organisation events detached successfully');
    }


    public function attachLocations($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $locationId = $input['location_id'];

      $organization->locations()->attach($locationId);

      return $this->sendResponse('success', 'Organisation locations attached successfully');
    }


    public function detachLocations($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();
      $locationId = $input['location_id'];

      $organization->locations()->detach($locationId);

      return $this->sendResponse('success', 'Organisation locations detached successfully');
    }


    public function attachEventOrgazanization($id, Request $request)
    {
      /** @var Organization $organization */
      $organization = $this->organizationRepository->findWithoutFail($id);

      if (empty($organization)) {
          return $this->sendError('Organization not found');
      }

      $input = $request->all();

      DB::beginTransaction();

      try {
          $event = Event::create([
            'name' => Input::get('name'),
            'description' => Input::get('description'),
            'start_date' => Input::get('start_date'),
            'free_or_paid' => Input::get('free_or_paid'),
            'end_date' => Input::get('end_date')
          ]);

          $organization->events()->attach($event->id);

          DB::commit();
          // all good
      } catch (\Exception $e) {
          DB::rollback();
          // something went wrong

          return response()->error($e->errorInfo[2], 500);
      }
      return $this->sendResponse($event->toArray(), 'Organisation event created successfully');
    }


      public function attachProjectOrgazanization($id, Request $request)
      {
        /** @var Organization $organization */
        $organization = $this->organizationRepository->findWithoutFail($id);

        if (empty($organization)) {
            return $this->sendError('Organization not found');
        }

        //$input = $request->all();

        DB::beginTransaction();

        try {
            $project = Project::create([
              'name' => Input::get('name'),
              'description' => Input::get('description'),
              'start_date' => Input::get('start_date'),
              'end_date' => Input::get('end_date')
            ]);

            $organization->events()->attach($project->id);

            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong

            return response()->error($e->errorInfo[2], 500);
        }

        return $this->sendResponse($project->toArray(), 'Organisation project created successfully');
    }

}
