<?php

namespace App\Livewire\Admin\Settings;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.app-layout')]
class Edit extends Component
{
    use WithFileUploads;

    public SchoolSetting $setting;

    public string $name = '';

    public string $address = '';

    public string $city = '';

    public string $phone = '';

    public string $email = '';

    public string $website = '';

    public $logo;

    public int $midterm_weight = 40;

    public int $final_weight = 60;

    public bool $saved = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'midterm_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'final_weight' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function mount(): void
    {
        $this->setting = SchoolSetting::current();

        $this->authorize('update', $this->setting);

        $this->name = $this->setting->name;
        $this->address = $this->setting->address ?? '';
        $this->city = $this->setting->city ?? '';
        $this->phone = $this->setting->phone ?? '';
        $this->email = $this->setting->email ?? '';
        $this->website = $this->setting->website ?? '';
        $this->midterm_weight = $this->setting->midterm_weight;
        $this->final_weight = $this->setting->final_weight;
    }

    public function save(): void
    {
        $this->authorize('update', $this->setting);

        $this->saved = false;

        $validated = $this->validate();
        unset($validated['logo']);

        if ($validated['midterm_weight'] + $validated['final_weight'] !== 100) {
            $this->addError('final_weight', 'Midterm and final weights must add up to 100.');

            return;
        }

        if ($this->logo) {
            if ($this->setting->logo_path) {
                Storage::disk('school-logo')->delete($this->setting->logo_path);
            }

            $filename = 'logo-'.now()->timestamp.'.'.$this->logo->extension();
            Storage::disk('school-logo')->putFileAs('', $this->logo, $filename);
            $validated['logo_path'] = $filename;
        }

        $this->setting->update($validated);
        $this->reset('logo');
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.admin.settings.edit');
    }
}
