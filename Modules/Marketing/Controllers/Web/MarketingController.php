<?php
declare(strict_types=1);

namespace Modules\Marketing\Controllers\Web;

use Core\Session;
use Modules\Billing\Repositories\PlanRepository;
use Modules\Marketing\Services\DemoRequestService;
final class MarketingController
{
    public function __construct(
        private readonly DemoRequestService $demoRequests,
        private readonly PlanRepository $plans
    ) {}

    public function home(array $params): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }

        $this->render('marketing/home', ['title' => 'SchoolMS — School Management, Simplified']);
    }

    public function pricing(array $params): void
    {
        $this->render('marketing/pricing', [
            'title' => 'Pricing — SchoolMS',
            'plans' => $this->plans->listActive(),
        ]);
    }

    public function demoForm(array $params): void
    {
        $this->render('marketing/demo_request', [
            'title'  => 'Request a Demo — SchoolMS',
            'errors' => Session::flash('errors') ?: [],
            'old'    => Session::flash('old') ?: [],
        ]);
    }

    public function submitDemo(array $params): void
    {
        $body = [
            'school_name'         => trim($_POST['school_name'] ?? ''),
            'contact_name'        => trim($_POST['contact_name'] ?? ''),
            'email'               => trim($_POST['email'] ?? ''),
            'phone'               => trim($_POST['phone'] ?? ''),
            'student_count_range' => $_POST['student_count_range'] ?? '',
            'message'             => trim($_POST['message'] ?? ''),
            'source'              => trim($_POST['source'] ?? 'demo_page'),
        ];


        if (trim($_POST['website'] ?? '') !== '') {
            $this->redirect('/demo/thank-you');
            return;
        }

        try {
            $this->demoRequests->submit($body, $_SERVER['REMOTE_ADDR'] ?? null);
            $this->redirect('/demo/thank-you');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/demo');
        }
    }

    public function demoThankYou(array $params): void
    {
        $this->render('marketing/demo_thank_you', ['title' => 'Thank You — SchoolMS']);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $title = $data['title'] ?? 'SchoolMS';

        $projectRoot = dirname(__DIR__, 4);

        ob_start();
        require $projectRoot . "/views/{$view}.php";
        $content = ob_get_clean();

        require $projectRoot . '/views/marketing/layout.php';
    }

    private function redirect(string $path): never
    {
        header("Location: {$path}");
        exit;
    }
}