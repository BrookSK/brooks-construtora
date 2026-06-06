<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Newsletter;

class NewsletterController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('newsletter')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $subscribers = Newsletter::all('subscribed_at DESC');

        $this->view('admin.newsletter.index', [
            'subscribers' => $subscribers,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function export(): void
    {
        $subscribers = Newsletter::getActiveSubscribers();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=newsletter_subscribers_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Nome', 'E-mail', 'Data de Inscrição', 'Status']);

        foreach ($subscribers as $sub) {
            fputcsv($output, [
                $sub['name'],
                $sub['email'],
                $sub['subscribed_at'],
                $sub['active'] ? 'Ativo' : 'Inativo',
            ]);
        }

        fclose($output);
        exit;
    }

    public function delete(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/newsletter');
            return;
        }

        $id = (int) $this->input('id');

        if ($id > 0) {
            Newsletter::deleteById($id);
            $this->setFlash('success', 'Inscrito removido com sucesso.');
        }

        $this->redirect('/admin/newsletter');
    }
}
