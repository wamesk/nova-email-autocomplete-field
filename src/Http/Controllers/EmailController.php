<?php

namespace Wame\NovaEmailAutocompleteField\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Wame\NovaEmailAutocompleteField\Http\Requests\EmailCheckExistsRequest;

class EmailController
{
    public function checkEmailExists(Request $request)
    {
        $email = $request->get('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'enabled' => true,
                'message' => __('nova-email-autocomplete-field.email.invalid'),
                'status' => 'invalid',
                'url' => null,
            ]);
        }

        $column = $request->get('column');
        $table = $request->get('table');
        if (!$column || !$table) {
            return response()->json([
                'enabled' => true,
                'message' => __('nova-email-autocomplete-field.table_and_column_is_required'),
                'status' => 'invalid',
                'url' => null,
            ]);
        }

        $query = DB::table($table)->where($column, $email);

        if (!empty($request->get('resourceId'))) {
            $query->where('id', '!=', $request->get('resourceId'));
        }

        $check = $query->select('id')->first();
        if ($check) {
            $resource = $request->get('unique_resource');
            if (!$resource) {
                $resource = $table;
            }

            return response()->json([
                'enabled' => true,
                'message' => __('nova-email-autocomplete-field.invalid'),
                'status' => 'invalid',
                'url' => config('nova.path') . 'resources/' . $resource . '/' . $check->id,
            ]);
        }

        return response()->json([
            'enabled' => true,
            'message' => __('nova-email-autocomplete-field.valid'),
            'status' => 'valid',
            'url' => null,
        ]);
    }
}