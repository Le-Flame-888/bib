-- Create a view to map n_emprunts to n_loan for compatibility
CREATE OR REPLACE VIEW n_loan AS
SELECT 
    id_emprunt as id,
    id_exemplaire as id_livre,
    id_utilisateur as user_id,
    date_emprunt as loan_date,
    date_retour_effective as return_date,
    date_retour_prevue as due_date,
    CASE 
        WHEN statut = 'actif' THEN 'actif'
        WHEN statut = 'rendu' THEN 'rendu'
        WHEN statut = 'en_retard' THEN 'en_retard'
        WHEN statut = 'perdu' THEN 'perdu'
        ELSE statut
    END as status
FROM n_emprunts;