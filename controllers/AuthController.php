<?php
class AuthController extends Controller {
    protected $User;

    public function __construct() {
        parent::__construct();
        $this->loadModel('User');
    }

    public function Connexion() {
        if ($this->isPost()) {
            $user_nom  = $this->getPost('user_nom');
            $user_login = $this->getPost('user_login');

            if (empty($user_nom) || empty($user_login)) {
                $this->setFlash('Veuillez remplir tous les champs', 'danger');
                $this->redirect('auth/Connexion.php');
                return;
            }

            $user = $this->User->findByLogin($user_login);
            
            if ($user && password_verify($user_login, $user['password'])) {
                // Remove password from session data
                unset($user['password']);
                
                // Set user session
                $_SESSION['user'] = $user;
                $_SESSION['logged_in'] = true;
                
                $this->setFlash('Connexion réussie', 'success');
                $this->redirect('');
            } else {
                $this->setFlash('Identifiants incorrects', 'danger');
                $this->redirect('auth/Connexion.php');
            }
        } else {
            $this->render('auth/Connexion.php', [
                'pageTitle' => 'Connexion - ' . APP_NAME
            ]);
        }
    }

    public function logout() {
        // Destroy the session
        session_destroy();
        
        // Redirect to home page
        $this->redirect('');
    }

    public function register() {
        if ($this->isPost()) {
            $user_nom = $this->getPost('user_nom');
            $user_login = $this->getPost('user_login');
            $user_password = $this->getPost('user_password');
            $user_confirm_password = $this->getPost('user_confirm_password');

            // Validation
            if (empty($user_nom) || empty($user_login) || empty($user_password) || empty($user_confirm_password)) {
                $this->setFlash('Veuillez remplir tous les champs', 'danger');
                $this->redirect('auth/register');
                return;
            }

            if ($user_password !== $user_confirm_password) {
                $this->setFlash('Les mots de passe ne correspondent pas', 'danger');
                $this->redirect('auth/register');
                return;
            }

            // Check if user already exists
            if ($this->User->findByLogin($user_login)) {
                $this->setFlash('Ce nom d\'utilisateur est déjà pris', 'danger');
                $this->redirect('auth/register');
                return;
            }

            if ($this->User->findByEmail($user_login)) {
                $this->setFlash('Cette adresse email est déjà utilisée', 'danger');
                $this->redirect('auth/register');
                return;
            }

            // Create user
            $userData = [
                'user_nom' => $user_nom,
                'user_login' => $user_login,
                'user_password' => password_hash($user_password, PASSWORD_DEFAULT),
                'role' => 'user',
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($this->User->create($userData)) {
                $this->setFlash('Compte créé avec succès. Vous pouvez maintenant vous connecter.', 'success');
                $this->redirect('auth/Connexion.php');
            } else {
                $this->setFlash('Une erreur est survenue lors de la création du compte', 'danger');
                $this->redirect('auth/register');
            }
        } 
    }

    public function resetPassword() {
        if ($this->isPost()) {
            $user_login = $this->getPost('user_login');

            if (empty($user_login)) {
                $this->setFlash('Veuillez entrer votre adresse email', 'danger');
                $this->redirect('auth/reset-password');
                return;
            }

            $user = $this->User->findByEmail($user_login);
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token in database
                $this->User->saveResetToken($user['id'], $token, $expiry);

                // Send reset email (implement email sending functionality)
                // TODO: Implement email sending

                $this->setFlash('Un email de réinitialisation a été envoyé à votre adresse', 'success');
                $this->redirect('auth/Connexion.php');
            } else {
                $this->setFlash('Aucun compte associé à cette adresse email', 'danger');
                $this->redirect('auth/reset-password');
            }
        } else {
            $this->render('auth/reset-password', [
                'pageTitle' => 'Réinitialisation du mot de passe - ' . APP_NAME
            ]);
        }
    }
} 