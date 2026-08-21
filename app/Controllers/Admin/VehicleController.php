<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\VehicleUsage;

class VehicleController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        // Qualquer usuário logado pode acessar o controle do veículo
    }

    /**
     * Listagem de registros + status atual
     */
    public function index(): void
    {
        $records = VehicleUsage::allOrdered();
        $stats = VehicleUsage::getStats();

        $this->view('admin.vehicle.index', [
            'records' => $records,
            'stats' => $stats,
            'user' => Auth::user(),
            'flash' => $this->getFlash(),
            'pageTitle' => 'Controle do Saveiro',
            'currentPage' => 'vehicle',
        ]);
    }

    /**
     * Registrar retirada do veículo
     */
    public function pickup(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/vehicle');
            return;
        }

        // Verificar se o veículo está disponível
        if (!VehicleUsage::isAvailable()) {
            $this->setFlash('error', 'O veículo já está em uso. Aguarde a devolução antes de registrar nova retirada.');
            $this->redirect('/admin/vehicle');
            return;
        }

        $driverName = trim($this->input('driver_name', ''));
        $pickupDate = $this->input('pickup_date', '');
        $pickupTime = $this->input('pickup_time', '');
        $pickupLocation = trim($this->input('pickup_location', ''));
        $pickupKm = (int) $this->input('pickup_km', 0);
        $destination = trim($this->input('destination', ''));
        $pickupNotes = trim($this->input('pickup_notes', ''));

        if (!$driverName || !$pickupDate || !$pickupTime || !$pickupLocation || !$pickupKm || !$destination) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/admin/vehicle');
            return;
        }

        // Validar KM (deve ser >= último KM registrado)
        $lastKm = VehicleUsage::getLastKm();
        if ($lastKm && $pickupKm < $lastKm) {
            $this->setFlash('error', "A quilometragem de saída ({$pickupKm}) não pode ser menor que a última registrada ({$lastKm}).");
            $this->redirect('/admin/vehicle');
            return;
        }

        VehicleUsage::registerPickup([
            'driver_name' => $driverName,
            'registered_by' => Auth::user()['name'],
            'registered_by_user_id' => Auth::id(),
            'pickup_date' => $pickupDate,
            'pickup_time' => $pickupTime,
            'pickup_location' => $pickupLocation,
            'pickup_km' => $pickupKm,
            'destination' => $destination,
            'pickup_notes' => $pickupNotes ?: null,
        ]);

        $this->setFlash('success', 'Retirada registrada com sucesso! O veículo agora está em uso por ' . $driverName . '.');
        $this->redirect('/admin/vehicle');
    }

    /**
     * Registrar devolução do veículo
     */
    public function returnVehicle(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/vehicle');
            return;
        }

        $id = (int) $this->input('id', 0);
        $record = VehicleUsage::find($id);

        if (!$record) {
            $this->setFlash('error', 'Registro não encontrado.');
            $this->redirect('/admin/vehicle');
            return;
        }

        if (!empty($record['return_date'])) {
            $this->setFlash('error', 'Este veículo já foi devolvido.');
            $this->redirect('/admin/vehicle');
            return;
        }

        $returnDate = $this->input('return_date', '');
        $returnTime = $this->input('return_time', '');
        $returnKm = (int) $this->input('return_km', 0);
        $returnNotes = trim($this->input('return_notes', ''));

        if (!$returnDate || !$returnTime || !$returnKm) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios da devolução.');
            $this->redirect('/admin/vehicle');
            return;
        }

        // KM de devolução deve ser >= KM de retirada
        if ($returnKm < $record['pickup_km']) {
            $this->setFlash('error', "A quilometragem de devolução ({$returnKm}) não pode ser menor que a de saída ({$record['pickup_km']}).");
            $this->redirect('/admin/vehicle');
            return;
        }

        VehicleUsage::registerReturn($id, [
            'return_date' => $returnDate,
            'return_time' => $returnTime,
            'return_km' => $returnKm,
            'return_notes' => $returnNotes ?: null,
            'returned_by' => Auth::user()['name'],
        ]);

        $kmRodados = $returnKm - $record['pickup_km'];
        $this->setFlash('success', "Devolução registrada! Veículo percorreu {$kmRodados} km nesta viagem.");
        $this->redirect('/admin/vehicle');
    }
}
