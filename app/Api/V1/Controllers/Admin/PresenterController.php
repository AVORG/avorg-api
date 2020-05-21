<?php

namespace App\Api\V1\Controllers\Admin;

use App\Api\V1\Requests\PresenterRequest;
use App\Presenter;
use App\Transformers\Admin\PresenterTransformer;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @group Presenter
 *
 * Endpoints for manipulating presenter catalog.
 */
class PresenterController extends BaseController {

   /**
    * Get presenters
    * 
    * Get all presenters.
    * @authenticated
    * @queryParam lang required string Example: en
    */
   public function all() {

      $presenter = Presenter::where($this->where)->paginate(config('avorg.page_size'));

      if ( $presenter->count() == 0 ) {
         return $this->response->errorNotFound("Presenters not found");
      }

      return $this->response->paginator($presenter, new PresenterTransformer);
   }

   /**
    * Get all presenters
    * 
    * This fetches all available presenters in the database table. Please utilize a caching
    * mechanism on the client side.
    * 
    * @authenticated
    * @queryParam lang required string Example: en
    */
   public function mass() {

      $this->where = array_merge($this->where, [
         'lang' => config('avorg.default_lang'),
      ]);

      $presenter = Presenter::where($this->where)->get();

      if ( $presenter->count() == 0 ) {
         return $this->response->errorNotFound("Presenters not found");
      }

      return $this->response->collection($presenter, new PresenterTransformer);
   }

   /**
    * Get one presenter
    *
    * @authenticated
    * @queryParam lang required string Example: en
    * @urlParam id required id of the presenter. Example: 1
    */
   public function one($id) {

      try {
         $item = Presenter::where($this->where)->findOrFail($id);
         return $this->response->item($item, new PresenterTransformer);
      } catch ( ModelNotFoundException $e) {
         return $this->response->errorNotFound("Presenter {$id} not found.");
      }
   }

   /**
	 * Create presenter
	 *
    * @authenticated
    * @queryParam lang required string Example: en
	 * @queryParam evalsRequired int
    * @queryParam salutation string
    * @queryParam givenName string
    * @queryParam surname string
    * @queryParam suffix string
    * @queryParam letters string
    * @queryParam hiragana string
    * @queryParam photo string
    * @queryParam summary string
    * @queryParam description string
    * @queryParam website string
    * @queryParam publicAddress string
    * @queryParam publicPhone string
    * @queryParam publicEmail string
    * @queryParam contactName string
    * @queryParam contactAddress string
    * @queryParam contactPhone string
    * @queryParam contactEmail string
    * @queryParam hidden integer
    * @queryParam notes string
	 */
   public function create(PresenterRequest $request) {

      $presenter = new Presenter();
      $this->setFields($request, $presenter);
      $presenter->save();

      return response()->json([
         'message' => 'Presenter added.',
         'status_code' => 201
      ], 201);
   }

   /**
	 * Update presenter
	 *
    * @authenticated
    * @queryParam id required integer
    * @queryParam lang required string Example: en
	 * @queryParam evalsRequired int
    * @queryParam salutation string
    * @queryParam givenName string
    * @queryParam surname string
    * @queryParam suffix string
    * @queryParam letters string
    * @queryParam hiragana string
    * @queryParam photo string
    * @queryParam summary string
    * @queryParam description string
    * @queryParam website string
    * @queryParam publicAddress string
    * @queryParam publicPhone string
    * @queryParam publicEmail string
    * @queryParam contactName string
    * @queryParam contactAddress string
    * @queryParam contactPhone string
    * @queryParam contactEmail string
    * @queryParam hidden integer
    * @queryParam notes string
	 */
   public function update(PresenterRequest $request) {

      try {
         $presenter = Presenter::findOrFail($request->id);
         $this->setFields($request, $presenter);
         $presenter->update();

         return response()->json([
            'message' => 'Presenter updated.',
            'status_code' => 201
         ], 201);
         
      } catch ( ModelNotFoundException $e ) {
         return $this->response->errorNotFound("Presenter {$request->id} not found.");
      }
   }

   /**
    * Delete presenter
    *
    * @authenticated
    * @queryParam id required id of the presenter. Example: 1
    */
   public function delete(PresenterRequest $request) {
      
      try {
         $presenter = Presenter::where(['active' => 1])->findOrFail($request->id);

         // To prevent orphans, prevent deletion of persons if recordings exist.
         if (!$presenter->recordings()->exists()) {
            // Soft delete.
            $presenter->active = 0;
            $presenter->save();

            return response()->json([
               'message' => "Presenter {$request->id} deleted.",
               'status_code' => 201
            ], 201);
         }
         else {
            return $this->response->errorNotFound("Presenter {$request->id} is referenced in a junction table thus can not be deleted.");
         }

      } catch ( ModelNotFoundException $e ) {
         return $this->response->errorNotFound("Presenter {$request->id} not found.");
      }
   }

   private function setFields(PresenterRequest $request, Presenter $presenter) {

      $presenter->evalsRequired = $request->evalsRequired;
      $presenter->salutation = $request->salutation;
      $presenter->givenName = $request->givenName;
      $presenter->surname = $request->surname;
      $presenter->suffix = $request->suffix;
      $presenter->letters = $request->letters;
      $presenter->hiragana = $request->hiragana;

      $suffix = (($request->suffix != '') ? ' ' . $request->suffix : '');
      $letters = (($request->letters != '') ? ', ' . $request->letters : '');
      $salutation = (($request->salutation != '') ? $request->salutation . ' ' : '');

      $presenter->nameGnfCasual = $request->givenName . ' ' . $request->surname . $suffix;
      $presenter->nameSnfCasual = $request->surname . ', ' . $request->givenName . $suffix;
      $presenter->nameGnfFormal = $salutation . $presenter->nameGnfCasual . $letters;
      $presenter->nameSnfFormal = $request->surname . ', ' . $salutation . $request->givenName . $suffix . $letters;
      $presenter->photo = $request->photo;
      $presenter->summary = $request->summary;
      $presenter->description = $request->description;
      $presenter->website = $request->website;
      $presenter->publicAddress = $request->publicAddress;
      $presenter->publicPhone = $request->publicPhone;
      $presenter->publicEmail = $request->publicEmail;
      $presenter->contactName = $request->contactName;
      $presenter->contactAddress = $request->contactAddress;
      $presenter->contactPhone = $request->contactPhone;
      $presenter->contactEmail = $request->contactEmail;
      $presenter->lang = $request->lang;
      $presenter->hiddenBySelf = $request->hidden;
      $presenter->hidden = $request->hidden;
      $presenter->notes = $request->notes;
      $presenter->active = 1;
   }
}