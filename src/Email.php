<?php

declare(strict_types = 1);

namespace Wame\NovaEmailAutocompleteField;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Fields\DependentFields;
use Laravel\Nova\Fields\SupportsDependentFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Email extends Text
{
    use SupportsDependentFields;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-email-autocomplete-field';

    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        $this->withMeta(['domains' => config('nova-email-autocomplete.domains', [])]);

        parent::__construct($name, $attribute, $resolveCallback);
    }

    public function domains(array $domains): Email
    {
        return $this->withMeta(['domains' => $domains]);
    }

    /**
     * @param mixed $requestAttribute
     * @param mixed $model
     * @param mixed $attribute
     *
     * @throws ValidationException
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute): void
    {
        $value = $request->get($attribute);
        if (isset($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([$attribute => __('validation.email', ['attribute' => $requestAttribute])]);
        }

        $model->{$attribute} = $value;
    }

    public function creationRules($rules)
    {
        $parent = parent::creationRules($rules);

        $this->uniqueRules($rules);

        return $parent;
    }

    public function updateRules($rules)
    {
        $parent = parent::updateRules($rules);

        $this->uniqueRules($rules);

        return $parent;
    }

    private function uniqueRules($rules): void
    {
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $this->uniqueRules($rule);
            }

            return;
        }

        if (is_string($rules) && Str::startsWith($rules, 'unique:')) {
            $rules = Str::replaceFirst('unique:', '', $rules);
            [$table, $column] = explode(',', $rules);

            $this->withMeta(['unique' => ['table' => $table, 'column' => $column]]);
        }
    }

    public function uniqueResource($resource)
    {
        $this->withMeta(['unique_resource' => $resource]);

        return $this;
    }
}
