<?php

require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/OrganizationModel.php';

class ProjectController {

    private ProjectModel $project;
    private OrganizationModel $organization;

    public function __construct() {
        $this->project      = new ProjectModel();
        $this->organization = new OrganizationModel();
    }

    public function createProject() {
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

        header('Content-Type: application/json');

        $data        = json_decode(file_get_contents('php://input'), true);
        $name        = trim($data['name'] ?? '');
        $description = isset($data['description']) ? trim($data['description']) : null;

        if ($name === '') {
            Response(400, false, "Project name is required");
        }

        $admin_id        = AuthMiddleware::adminId();
        $organization_id = AuthMiddleware::organization($this->organization, $admin_id);

        $result = $this->project->createProject(
            $organization_id,
            $name,
            $description,
            $admin_id,
            'active'
        );

        Response(201, true, "Project created", $result);
    }

    public function deleteProject() {
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['project_id'])) {
            Response(400, false, "project_id is required");
        }

        // Ownership check — project must belong to this admin's org
        $admin_id        = AuthMiddleware::adminId();
        $organization_id = AuthMiddleware::organization($this->organization, $admin_id);
        $project         = $this->project->getProjectById((int) $data['project_id']);

        if (!$project) {
            Response(404, false, "Project not found");
        }

        if ((int) $project['organization_id'] !== (int) $organization_id) {
            Response(403, false, "You do not have permission to delete this project");
        }

        $result = $this->project->deleteProject((int) $data['project_id']);
        Response(200, true, $result['message']);
    }

    public function editProject() {
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['project_id'], $data['name'], $data['status'])) {
            Response(400, false, "Required fields missing");
        }

        $name        = trim($data['name']);
        $description = isset($data['description']) ? trim($data['description']) : null;
        $status      = trim($data['status']);

        if ($name === '') {
            Response(400, false, "Project name cannot be empty");
        }

        // Ownership check
        $admin_id        = AuthMiddleware::adminId();
        $organization_id = AuthMiddleware::organization($this->organization, $admin_id);
        $project         = $this->project->getProjectById((int) $data['project_id']);

        if (!$project) {
            Response(404, false, "Project not found");
        }

        if ((int) $project['organization_id'] !== (int) $organization_id) {
            Response(403, false, "You do not have permission to edit this project");
        }

        $result = $this->project->updateProject(
            (int) $data['project_id'],
            $name,
            $description,
            $status
        );

        Response(200, true, $result['message'], [
            'project_id'  => $result['project_id'],
            'name'        => $name,
            'description' => $description,
            'status'      => $status
        ]);
    }

    public function getAllProject() {
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

        header('Content-Type: application/json');

        $admin_id        = AuthMiddleware::adminId();
        $organization_id = AuthMiddleware::organization($this->organization, $admin_id);
        $projects        = $this->project->getProjectsByOrganization($organization_id);

        Response(200, true, "Projects fetched successfully", $projects);
    }

   
    public function getProjectByID() {
        if (!AuthMiddleware::isLoggedIn()) {
                Response(401, false, "Unauthorized");
            }

            header('Content-Type: application/json');

            $project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

            if (!$project_id) {
                Response(400, false, "project_id is required");
            }

            $result = $this->project->getProjectById($project_id);

            if (!$result) {
                Response(404, false, "Project not found");
            }

            Response(200, true, "Project fetched", $result);
        }
}