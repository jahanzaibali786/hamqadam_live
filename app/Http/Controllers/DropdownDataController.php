<?php

namespace App\Http\Controllers;

use App\Models\AnnualSalaryRange;
use App\Models\Caste;
use App\Models\City;
use App\Models\Country;
use App\Models\Degree;
use App\Models\EducationLevel;
use App\Models\FamilyStatus;
use App\Models\FamilyValue;
use App\Models\FieldOfStudy;
use App\Models\Institution;
use App\Models\MaritalStatus;
use App\Models\MemberLanguage;
use App\Models\OnBehalf;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use App\Models\Religion;
use App\Models\SchoolOfThought;
use App\Models\SectMain;
use App\Models\State;
use App\Models\SubCaste;
use App\Models\Tradition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Redirect;
use Validator;

class DropdownDataController extends Controller
{
    private array $groups = [];
    private array $items = [];

    public function __construct()
    {
        $this->groups = [
            'identity' => translate('Identity & Status'),
            'location' => translate('Location'),
            'religion' => translate('Religion & Community'),
            'education' => translate('Education'),
            'career' => translate('Career'),
            'family' => translate('Family'),
        ];

        $this->items = $this->buildItems();
    }

    public function index(Request $request)
    {
        $activeTab = $request->get('tab', array_key_first($this->groups));
        if (! array_key_exists($activeTab, $this->groups)) {
            $activeTab = array_key_first($this->groups);
        }

        $searches = [];
        $records = [];

        foreach ($this->itemsByGroup($activeTab) as $type => $config) {
            $searchKey = 'search_'.$type;
            $searches[$type] = $request->get($searchKey);
            $query = $this->queryForType($type, $searches[$type]);
            $records[$type] = $query->paginate(10, ['*'], $type.'_page')->appends($request->query());
        }

        return view('admin.website_settings.dropdown_data.index', [
            'groups' => $this->groups,
            'activeTab' => $activeTab,
            'items' => $this->items,
            'groupItems' => $this->itemsByGroup($activeTab),
            'records' => $records,
            'searches' => $searches,
            'lookups' => $this->lookupOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->input('type');
        $config = $this->itemConfig($type);

        $validator = Validator::make($request->all(), $config['rules'], $config['messages']);
        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator)->withInput();
        }

        DB::transaction(function () use ($request, $config, $type) {
            $modelClass = $config['model'];
            $record = new $modelClass();
            $this->fillRecord($record, $config, $request);
            $record->save();
        });

        flash(translate($config['success_store']))->success();
        return redirect()->route('website.dropdown_data.index', ['tab' => $config['group']]);
    }

    public function edit(string $type, string $id)
    {
        $config = $this->itemConfig($type);
        $modelClass = $config['model'];
        $record = $modelClass::findOrFail(decrypt($id));

        return view('admin.website_settings.dropdown_data.edit', [
            'type' => $type,
            'config' => $config,
            'record' => $record,
            'lookups' => $this->lookupOptions(),
        ]);
    }

    public function update(Request $request, string $type, string $id)
    {
        $config = $this->itemConfig($type);

        $validator = Validator::make($request->all(), $config['rules'], $config['messages']);
        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $modelClass = $config['model'];
        $record = $modelClass::findOrFail($id);

        DB::transaction(function () use ($request, $config, $record) {
            $this->fillRecord($record, $config, $request);
            $record->save();
        });

        flash(translate($config['success_update']))->success();
        return redirect()->route('website.dropdown_data.index', ['tab' => $config['group']]);
    }

    public function destroy(string $type, string $id)
    {
        $config = $this->itemConfig($type);
        $modelClass = $config['model'];
        $record = $modelClass::findOrFail($id);

        DB::transaction(function () use ($type, $record) {
            $this->cascadeDelete($type, $record);
            $record->delete();
        });

        flash(translate($config['success_delete']))->success();
        return redirect()->route('website.dropdown_data.index', ['tab' => $config['group']]);
    }

    private function buildItems(): array
    {
        return [
            'on_behalves' => [
                'group' => 'identity',
                'title' => translate('On Behalves'),
                'model' => OnBehalf::class,
                'rules' => ['name' => ['required', 'max:255']],
                'messages' => [
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New on behalf has been added successfully'),
                'success_update' => translate('On behalf info has been updated successfully'),
                'success_delete' => translate('On behalf info has been deleted successfully'),
            ],
            'member_languages' => [
                'group' => 'identity',
                'title' => translate('Member Languages'),
                'model' => MemberLanguage::class,
                'rules' => ['name' => ['required', 'max:255']],
                'messages' => [
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New member language has been added successfully'),
                'success_update' => translate('Member language has been updated successfully'),
                'success_delete' => translate('Member language has been deleted successfully'),
            ],
            'marital_statuses' => [
                'group' => 'identity',
                'title' => translate('Marital Statuses'),
                'model' => MaritalStatus::class,
                'rules' => ['name' => ['required', 'max:255']],
                'messages' => [
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New marital status has been added successfully'),
                'success_update' => translate('Marital status has been updated successfully'),
                'success_delete' => translate('Marital status has been deleted successfully'),
            ],
            'family_values' => [
                'group' => 'family',
                'title' => translate('Family Values'),
                'model' => FamilyValue::class,
                'rules' => ['name' => ['required', 'max:255']],
                'messages' => [
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New family value has been added successfully'),
                'success_update' => translate('Family value has been updated successfully'),
                'success_delete' => translate('Family value has been deleted successfully'),
            ],
            'family_statuses' => [
                'group' => 'family',
                'title' => translate('Family Statuses'),
                'model' => FamilyStatus::class,
                'rules' => ['name' => ['required', 'max:255']],
                'messages' => [
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New family status has been added successfully'),
                'success_update' => translate('Family status has been updated successfully'),
                'success_delete' => translate('Family status has been deleted successfully'),
            ],
            'countries' => [
                'group' => 'location',
                'title' => translate('Countries'),
                'model' => Country::class,
                'rules' => [
                    'name' => ['required', 'max:255'],
                    'status' => ['nullable', 'in:0,1'],
                ],
                'messages' => [
                    'name.required' => translate('Country name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                    ['name' => 'status', 'label' => translate('Active'), 'type' => 'toggle'],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                    ['label' => translate('Status'), 'value' => fn ($record) => $record->status ? translate('Active') : translate('Inactive')],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New country has been added successfully'),
                'success_update' => translate('Country info updated successfully.'),
                'success_delete' => translate('Country deleted successfully'),
            ],
            'states' => [
                'group' => 'location',
                'title' => translate('States'),
                'model' => State::class,
                'rules' => [
                    'country_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                ],
                'messages' => [
                    'country_id.required' => translate('Country is required'),
                    'name.required' => translate('State name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'country_id', 'label' => translate('Country'), 'type' => 'select', 'options_key' => 'countries', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Country'), 'value' => fn ($record) => optional($record->country)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'relations' => ['country'],
                'searchable' => ['name'],
                'success_store' => translate('New state has been added successfully'),
                'success_update' => translate('State info has been updated successfully'),
                'success_delete' => translate('State info has been deleted successfully'),
            ],
            'cities' => [
                'group' => 'location',
                'title' => translate('Cities'),
                'model' => City::class,
                'rules' => [
                    'country_id' => ['required', 'integer'],
                    'state_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                ],
                'messages' => [
                    'country_id.required' => translate('Country is required'),
                    'state_id.required' => translate('State is required'),
                    'name.required' => translate('City name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'country_id', 'label' => translate('Country'), 'type' => 'select', 'options_key' => 'countries', 'required' => true],
                    ['name' => 'state_id', 'label' => translate('State'), 'type' => 'select', 'options_key' => 'states', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Country'), 'value' => fn ($record) => optional(optional($record->state)->country)->name],
                    ['label' => translate('State'), 'value' => fn ($record) => optional($record->state)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'relations' => ['state.country'],
                'searchable' => ['name'],
                'success_store' => translate('New city has been added successfully'),
                'success_update' => translate('City info has been updated successfully'),
                'success_delete' => translate('City info has been deleted successfully'),
            ],
            'religions' => [
                'group' => 'religion',
                'title' => translate('Religions'),
                'model' => Religion::class,
                'rules' => ['name' => ['required', 'max:255']],
                'messages' => [
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New religion has been added successfully'),
                'success_update' => translate('Religion info has been updated successfully'),
                'success_delete' => translate('Religion info has been deleted successfully'),
            ],
            'castes' => [
                'group' => 'religion',
                'title' => translate('Castes'),
                'model' => Caste::class,
                'rules' => [
                    'religion_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                ],
                'messages' => [
                    'religion_id.required' => translate('Religion is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'religion_id', 'label' => translate('Religion'), 'type' => 'select', 'options_key' => 'religions', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Religion'), 'value' => fn ($record) => optional($record->religion)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'relations' => ['religion'],
                'searchable' => ['name'],
                'success_store' => translate('New caste has been added successfully'),
                'success_update' => translate('Caste info has been updated successfully'),
                'success_delete' => translate('Caste info has been deleted successfully'),
            ],
            'sub_castes' => [
                'group' => 'religion',
                'title' => translate('Sub Castes'),
                'model' => SubCaste::class,
                'rules' => [
                    'caste_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                ],
                'messages' => [
                    'caste_id.required' => translate('Caste is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'caste_id', 'label' => translate('Caste'), 'type' => 'select', 'options_key' => 'castes', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Caste'), 'value' => fn ($record) => optional($record->caste)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'relations' => ['caste'],
                'searchable' => ['name'],
                'success_store' => translate('New sub caste has been added successfully'),
                'success_update' => translate('Sub caste info has been updated successfully'),
                'success_delete' => translate('Sub caste info has been deleted successfully'),
            ],
            'sect_mains' => [
                'group' => 'religion',
                'title' => translate('Main Sects'),
                'model' => SectMain::class,
                'rules' => [
                    'religion_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                ],
                'messages' => [
                    'religion_id.required' => translate('Religion is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'religion_id', 'label' => translate('Religion'), 'type' => 'select', 'options_key' => 'religions', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Religion'), 'value' => fn ($record) => optional($record->religion)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'relations' => ['religion'],
                'searchable' => ['name'],
                'success_store' => translate('New sect has been added successfully'),
                'success_update' => translate('Sect info has been updated successfully'),
                'success_delete' => translate('Sect info has been deleted successfully'),
            ],
            'school_of_thoughts' => [
                'group' => 'religion',
                'title' => translate('Schools of Thought'),
                'model' => SchoolOfThought::class,
                'rules' => [
                    'sect_main_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                ],
                'messages' => [
                    'sect_main_id.required' => translate('Main sect is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'sect_main_id', 'label' => translate('Main Sect'), 'type' => 'select', 'options_key' => 'sect_mains', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Main Sect'), 'value' => fn ($record) => optional($record->sectMain)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'relations' => ['sectMain'],
                'searchable' => ['name'],
                'success_store' => translate('New school of thought has been added successfully'),
                'success_update' => translate('School of thought info has been updated successfully'),
                'success_delete' => translate('School of thought info has been deleted successfully'),
            ],
            'traditions' => [
                'group' => 'religion',
                'title' => translate('Traditions'),
                'model' => Tradition::class,
                'rules' => [
                    'school_of_thought_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                ],
                'messages' => [
                    'school_of_thought_id.required' => translate('School of thought is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'school_of_thought_id', 'label' => translate('School of Thought'), 'type' => 'select', 'options_key' => 'school_of_thoughts', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('School of Thought'), 'value' => fn ($record) => optional($record->schoolOfThought)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                ],
                'relations' => ['schoolOfThought'],
                'searchable' => ['name'],
                'success_store' => translate('New tradition has been added successfully'),
                'success_update' => translate('Tradition info has been updated successfully'),
                'success_delete' => translate('Tradition info has been deleted successfully'),
            ],
            'education_levels' => [
                'group' => 'education',
                'title' => translate('Education Levels'),
                'model' => EducationLevel::class,
                'rules' => ['name' => ['required', 'max:255']],
                'messages' => [
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                    ['name' => 'sort_order', 'label' => translate('Sort Order'), 'type' => 'number'],
                    ['name' => 'is_active', 'label' => translate('Active'), 'type' => 'toggle'],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                    ['label' => translate('Status'), 'value' => fn ($record) => $record->is_active ? translate('Active') : translate('Inactive')],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New education level has been added successfully'),
                'success_update' => translate('Education level has been updated successfully'),
                'success_delete' => translate('Education level has been deleted successfully'),
            ],
            'degrees' => [
                'group' => 'education',
                'title' => translate('Degrees'),
                'model' => Degree::class,
                'rules' => [
                    'education_level_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                    'category' => ['nullable', 'max:255'],
                    'sort_order' => ['nullable', 'integer'],
                    'is_active' => ['nullable', 'in:0,1'],
                ],
                'messages' => [
                    'education_level_id.required' => translate('Education level is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'education_level_id', 'label' => translate('Education Level'), 'type' => 'select', 'options_key' => 'education_levels', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                    ['name' => 'category', 'label' => translate('Category'), 'type' => 'text'],
                    ['name' => 'sort_order', 'label' => translate('Sort Order'), 'type' => 'number'],
                    ['name' => 'is_active', 'label' => translate('Active'), 'type' => 'toggle'],
                ],
                'columns' => [
                    ['label' => translate('Education Level'), 'value' => fn ($record) => optional($record->educationLevel)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                    ['label' => translate('Category'), 'value' => fn ($record) => $record->category],
                    ['label' => translate('Status'), 'value' => fn ($record) => $record->is_active ? translate('Active') : translate('Inactive')],
                ],
                'relations' => ['educationLevel'],
                'searchable' => ['name', 'category'],
                'success_store' => translate('New degree has been added successfully'),
                'success_update' => translate('Degree info has been updated successfully'),
                'success_delete' => translate('Degree info has been deleted successfully'),
            ],
            'fields_of_study' => [
                'group' => 'education',
                'title' => translate('Fields of Study'),
                'model' => FieldOfStudy::class,
                'rules' => [
                    'degree_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                    'sort_order' => ['nullable', 'integer'],
                    'is_active' => ['nullable', 'in:0,1'],
                ],
                'messages' => [
                    'degree_id.required' => translate('Degree is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'degree_id', 'label' => translate('Degree'), 'type' => 'select', 'options_key' => 'degrees', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                    ['name' => 'sort_order', 'label' => translate('Sort Order'), 'type' => 'number'],
                    ['name' => 'is_active', 'label' => translate('Active'), 'type' => 'toggle'],
                ],
                'columns' => [
                    ['label' => translate('Degree'), 'value' => fn ($record) => optional($record->degree)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                    ['label' => translate('Status'), 'value' => fn ($record) => $record->is_active ? translate('Active') : translate('Inactive')],
                ],
                'relations' => ['degree'],
                'searchable' => ['name'],
                'success_store' => translate('New field of study has been added successfully'),
                'success_update' => translate('Field of study info has been updated successfully'),
                'success_delete' => translate('Field of study info has been deleted successfully'),
            ],
            'institutions' => [
                'group' => 'education',
                'title' => translate('Institutions'),
                'model' => Institution::class,
                'rules' => [
                    'country_id' => ['required', 'integer'],
                    'state_id' => ['required', 'integer'],
                    'city_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                    'type' => ['nullable', 'max:100'],
                    'description' => ['nullable', 'max:1000'],
                    'website' => ['nullable', 'max:255'],
                    'sort_order' => ['nullable', 'integer'],
                    'is_active' => ['nullable', 'in:0,1'],
                ],
                'messages' => [
                    'country_id.required' => translate('Country is required'),
                    'state_id.required' => translate('State is required'),
                    'city_id.required' => translate('City is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'country_id', 'label' => translate('Country'), 'type' => 'select', 'options_key' => 'countries', 'required' => true],
                    ['name' => 'state_id', 'label' => translate('State'), 'type' => 'select', 'options_key' => 'states', 'required' => true],
                    ['name' => 'city_id', 'label' => translate('City'), 'type' => 'select', 'options_key' => 'cities', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => translate('Type'), 'type' => 'select', 'options' => [
                        'University' => 'University',
                        'College' => 'College',
                        'Institute' => 'Institute',
                        'School' => 'School',
                        'Madarsa' => 'Madarsa',
                        'Other' => 'Other',
                    ]],
                    ['name' => 'description', 'label' => translate('Description'), 'type' => 'textarea'],
                    ['name' => 'website', 'label' => translate('Website'), 'type' => 'text'],
                    ['name' => 'sort_order', 'label' => translate('Sort Order'), 'type' => 'number'],
                    ['name' => 'is_active', 'label' => translate('Active'), 'type' => 'toggle'],
                ],
                'columns' => [
                    ['label' => translate('Institution'), 'value' => fn ($record) => $record->name],
                    ['label' => translate('Type'), 'value' => fn ($record) => $record->type],
                    ['label' => translate('Location'), 'value' => fn ($record) => trim(implode(', ', array_filter([
                        optional($record->city)->name,
                        optional($record->state)->name,
                        optional($record->country)->name,
                    ])))],
                    ['label' => translate('Status'), 'value' => fn ($record) => $record->is_active ? translate('Active') : translate('Inactive')],
                ],
                'relations' => ['country', 'state', 'city'],
                'searchable' => ['name', 'type'],
                'success_store' => translate('New institution has been added successfully'),
                'success_update' => translate('Institution info has been updated successfully'),
                'success_delete' => translate('Institution info has been deleted successfully'),
            ],
            'profession_categories' => [
                'group' => 'career',
                'title' => translate('Profession Categories'),
                'model' => ProfessionCategory::class,
                'rules' => ['name' => ['required', 'max:255']],
                'messages' => [
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                    ['name' => 'sort_order', 'label' => translate('Sort Order'), 'type' => 'number'],
                    ['name' => 'is_active', 'label' => translate('Active'), 'type' => 'toggle'],
                ],
                'columns' => [
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                    ['label' => translate('Status'), 'value' => fn ($record) => $record->is_active ? translate('Active') : translate('Inactive')],
                ],
                'searchable' => ['name'],
                'success_store' => translate('New profession category has been added successfully'),
                'success_update' => translate('Profession category has been updated successfully'),
                'success_delete' => translate('Profession category has been deleted successfully'),
            ],
            'professions' => [
                'group' => 'career',
                'title' => translate('Professions'),
                'model' => Profession::class,
                'rules' => [
                    'profession_category_id' => ['required', 'integer'],
                    'name' => ['required', 'max:255'],
                    'sort_order' => ['nullable', 'integer'],
                    'is_active' => ['nullable', 'in:0,1'],
                ],
                'messages' => [
                    'profession_category_id.required' => translate('Profession category is required'),
                    'name.required' => translate('Name is required'),
                    'name.max' => translate('Max 255 characters'),
                ],
                'fields' => [
                    ['name' => 'profession_category_id', 'label' => translate('Profession Category'), 'type' => 'select', 'options_key' => 'profession_categories', 'required' => true],
                    ['name' => 'name', 'label' => translate('Name'), 'type' => 'text', 'required' => true],
                    ['name' => 'sort_order', 'label' => translate('Sort Order'), 'type' => 'number'],
                    ['name' => 'is_active', 'label' => translate('Active'), 'type' => 'toggle'],
                ],
                'columns' => [
                    ['label' => translate('Category'), 'value' => fn ($record) => optional($record->professionCategory)->name],
                    ['label' => translate('Name'), 'value' => fn ($record) => $record->name],
                    ['label' => translate('Status'), 'value' => fn ($record) => $record->is_active ? translate('Active') : translate('Inactive')],
                ],
                'relations' => ['professionCategory'],
                'searchable' => ['name'],
                'success_store' => translate('New profession has been added successfully'),
                'success_update' => translate('Profession has been updated successfully'),
                'success_delete' => translate('Profession has been deleted successfully'),
            ],
            'annual_salary_ranges' => [
                'group' => 'career',
                'title' => translate('Annual Salary Ranges'),
                'model' => AnnualSalaryRange::class,
                'rules' => [
                    'min_salary' => ['required', 'numeric'],
                    'max_salary' => ['required', 'numeric'],
                ],
                'messages' => [
                    'min_salary.required' => translate('Minimum Salary is required'),
                    'max_salary.required' => translate('Maximum Salary is required'),
                ],
                'fields' => [
                    ['name' => 'min_salary', 'label' => translate('Minimum Salary'), 'type' => 'number', 'required' => true],
                    ['name' => 'max_salary', 'label' => translate('Maximum Salary'), 'type' => 'number', 'required' => true],
                ],
                'columns' => [
                    ['label' => translate('Range'), 'value' => fn ($record) => number_format((float) $record->min_salary).' - '.number_format((float) $record->max_salary)],
                ],
                'searchable' => ['min_salary', 'max_salary'],
                'success_store' => translate('New annual salary range has been added successfully'),
                'success_update' => translate('Annual salary range has been updated successfully'),
                'success_delete' => translate('Annual salary range has been deleted successfully'),
            ],
        ];
    }

    private function itemConfig(string $type): array
    {
        if (! isset($this->items[$type])) {
            abort(404);
        }

        return $this->items[$type];
    }

    private function itemsByGroup(string $group): array
    {
        return array_filter($this->items, static fn ($config) => $config['group'] === $group);
    }

    private function lookupOptions(): array
    {
        return [
            'countries' => Country::orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
            'cities' => City::with(['state.country'])->orderBy('name')->get(),
            'religions' => Religion::orderBy('name')->get(),
            'castes' => Caste::with('religion')->orderBy('name')->get(),
            'sect_mains' => SectMain::with('religion')->orderBy('name')->get(),
            'school_of_thoughts' => SchoolOfThought::with('sectMain')->orderBy('name')->get(),
            'education_levels' => EducationLevel::orderBy('sort_order')->orderBy('name')->get(),
            'degrees' => Degree::with('educationLevel')->orderBy('sort_order')->orderBy('name')->get(),
            'profession_categories' => ProfessionCategory::orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    private function queryForType(string $type, ?string $search = null)
    {
        $config = $this->itemConfig($type);
        $modelClass = $config['model'];
        $query = $modelClass::query();

        foreach ($config['relations'] ?? [] as $relation) {
            $query->with($relation);
        }

        if (! empty($config['order_by'])) {
            foreach ($config['order_by'] as $orderBy) {
                $query->orderBy($orderBy[0], $orderBy[1]);
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        if ($search) {
            $query->where(function ($builder) use ($search, $config) {
                foreach ($config['searchable'] ?? ['name'] as $column) {
                    if ($column === 'min_salary' || $column === 'max_salary') {
                        $builder->orWhere($column, 'like', '%'.$search.'%');
                    } else {
                        $builder->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        }

        return $query;
    }

    private function fillRecord(object $record, array $config, Request $request): void
    {
        foreach ($config['fields'] as $field) {
            $name = $field['name'];

            if ($field['type'] === 'toggle') {
                $record->{$name} = $request->has($name) ? 1 : 0;
                continue;
            }

            if ($name === 'name') {
                $record->name = $request->input('name');
                if (property_exists($record, 'slug') || isset($record->slug) || array_key_exists('slug', $record->getAttributes() ?? [])) {
                    $record->slug = Str::slug($request->input('name'));
                }
                continue;
            }

            $record->{$name} = $request->input($name);
        }

        if (isset($record->status) && array_key_exists('status', $request->all())) {
            $record->status = $request->has('status') ? 1 : 0;
        }

        if ($record instanceof Institution && ! empty($request->input('name'))) {
            $record->slug = Str::slug($request->input('name'));
        }
    }

    private function cascadeDelete(string $type, object $record): void
    {
        switch ($type) {
            case 'countries':
                foreach ($record->states as $state) {
                    foreach ($state->cities as $city) {
                        $city->delete();
                    }
                    $state->delete();
                }
                break;

            case 'states':
                foreach ($record->cities as $city) {
                    $city->delete();
                }
                break;

            case 'religions':
                foreach ($record->castes as $caste) {
                    foreach ($caste->sub_castes as $subCaste) {
                        $subCaste->delete();
                    }
                    $caste->delete();
                }
                foreach ($record->sectMains ?? [] as $sectMain) {
                    foreach ($sectMain->schoolsOfThoughts ?? [] as $school) {
                        foreach ($school->traditions ?? [] as $tradition) {
                            $tradition->delete();
                        }
                        $school->delete();
                    }
                    $sectMain->delete();
                }
                break;

            case 'castes':
                foreach ($record->sub_castes as $subCaste) {
                    $subCaste->delete();
                }
                break;

            case 'sect_mains':
                foreach ($record->schoolsOfThoughts ?? [] as $school) {
                    foreach ($school->traditions ?? [] as $tradition) {
                        $tradition->delete();
                    }
                    $school->delete();
                }
                break;

            case 'school_of_thoughts':
                foreach ($record->traditions as $tradition) {
                    $tradition->delete();
                }
                break;

            case 'education_levels':
                foreach ($record->degrees as $degree) {
                    foreach ($degree->fieldsOfStudy as $fieldOfStudy) {
                        $fieldOfStudy->delete();
                    }
                    $degree->delete();
                }
                break;

            case 'degrees':
                foreach ($record->fieldsOfStudy as $fieldOfStudy) {
                    $fieldOfStudy->delete();
                }
                break;

            case 'profession_categories':
                foreach ($record->professions as $profession) {
                    $profession->delete();
                }
                break;
        }
    }
}
