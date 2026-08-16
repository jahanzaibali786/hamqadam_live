<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $templates = class_exists(SmsTemplate::class) ? SmsTemplate::query()->paginate(15) : collect();

        return view()->exists('admin.settings.email_templates.index')
            ? view('admin.settings.email_templates.index', compact('templates'))
            : redirect()->route('admin.dashboard');
    }

    public function edit(SmsTemplate $smsTemplate)
    {
        return view()->exists('admin.settings.email_templates.index')
            ? view('admin.settings.email_templates.index', ['templates' => collect([$smsTemplate])])
            : redirect()->route('sms-templates.index');
    }

    public function update(Request $request, SmsTemplate $smsTemplate)
    {
        $smsTemplate->fill($request->only(['sms_body', 'template_id', 'status']))->save();

        flash(translate('SMS template has been updated successfully'))->success();

        return redirect()->route('sms-templates.index');
    }
}

