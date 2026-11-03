<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\GiftTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class GiftCatalogWebController extends Controller
{
    private const UPLOAD_DIR = 'uploads/gifts';

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->user_type === 'admin'
                || ($request->user()?->user_type === 'staff' && $request->user()?->admin_identifier === 'subadmin'),
            403
        );
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        return view('admin.gifts.index', [
            'gifts' => Gift::orderBy('sort_order')->orderBy('id')->get(),
            'transactions' => GiftTransaction::with(['gift', 'sender', 'receiver'])->latest()->paginate(15),
        ]);
    }

    public function save(Request $request)
    {
        $this->authorizeAdmin($request);
        $id = $request->integer('gift_id') ?: null;
        $gift = $id ? Gift::findOrFail($id) : null;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'coins' => ['required', 'integer', 'min:1'],
            'category' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            // Files are required on create, optional on edit (keep the old one).
            'thumbnail_file' => [$gift ? 'nullable' : 'required', 'file', 'mimes:png,jpg,jpeg,webp,gif', 'max:2048'],
            'animation_file' => [$gift ? 'nullable' : 'required', 'file', 'max:20480'],
        ]);

        $attrs = [
            'name' => $data['name'],
            'coins' => $data['coins'],
            'category' => $data['category'] ?: 'general',
            'sort_order' => $data['sort_order'] ?? ($gift?->sort_order ?? (int) Gift::max('sort_order') + 1),
            'is_active' => $request->boolean('is_active'),
        ];

        if (! $gift) {
            $base = Str::slug($data['name']) ?: 'gift';
            $slug = $base;
            for ($i = 2; Gift::where('slug', $slug)->exists(); $i++) {
                $slug = $base . '-' . $i;
            }
            $attrs['slug'] = $slug;
        }

        if ($file = $request->file('thumbnail_file')) {
            $attrs['thumbnail'] = $this->storeFile($file, 'thumb');
        }

        if ($file = $request->file('animation_file')) {
            // SVGA has no registered MIME type, so validate by extension.
            $ext = strtolower($file->getClientOriginalExtension());
            if (! isset(Gift::MEDIA_TYPES[$ext])) {
                return back()->withInput()->withErrors(['animation_file' => 'Animation must be a GIF, SVGA, MP4 or Lottie JSON file.']);
            }
            $attrs['animated_asset'] = $this->storeFile($file, 'anim');
            // Guarded so uploads still work if the media_type migration hasn't run;
            // the type is then inferred from the file extension.
            if (\Illuminate\Support\Facades\Schema::hasColumn('gifts', 'media_type')) {
                $attrs['media_type'] = Gift::MEDIA_TYPES[$ext];
            }
        }

        $gift ? $gift->update($attrs) : Gift::create($attrs);

        return back()->with('success', 'Gift saved.');
    }

    public function deactivate(Request $request, Gift $gift)
    {
        $this->authorizeAdmin($request);
        $gift->update(['is_active' => ! $gift->is_active]);

        return back()->with('success', $gift->is_active ? 'Gift activated.' : 'Gift deactivated without deleting history.');
    }

    private function storeFile(UploadedFile $file, string $prefix): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $name = $prefix . '_' . Str::random(20) . '.' . $ext;
        $file->move(public_path(self::UPLOAD_DIR), $name);

        return self::UPLOAD_DIR . '/' . $name;
    }
}
