// Bibliothèque municipale de Port-Cartier — comportements côté client
document.addEventListener('DOMContentLoaded', () => {

  // 1) Demander confirmation avant une annulation ou un emprunt (<form data-confirm="Message ?">)
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', ev => {
      if (!confirm(form.dataset.confirm)) ev.preventDefault();
    });
  });

  // 2) Validation Bootstrap des formulaires (classe .needs-validation)
  document.querySelectorAll('form.needs-validation').forEach(form => {
    form.addEventListener('submit', ev => {
      if (!form.checkValidity()) {
        ev.preventDefault();
        ev.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  // 3) Confirmation du mot de passe (création de comptes)
  const mdp  = document.getElementById('mot_de_passe');
  const mdp2 = document.getElementById('mot_de_passe2');
  if (mdp && mdp2) {
    const verifier = () => mdp2.setCustomValidity(mdp.value === mdp2.value ? '' : 'Les mots de passe ne correspondent pas.');
    mdp.addEventListener('input', verifier);
    mdp2.addEventListener('input', verifier);
  }

  // 4) Afficher / masquer le mot de passe
  document.querySelectorAll('[data-toggle-password]').forEach(btn => {
    btn.addEventListener('click', () => {
      const champ = document.getElementById(btn.dataset.togglePassword);
      const visible = champ.type === 'text';
      champ.type = visible ? 'password' : 'text';
      btn.innerHTML = visible ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
  });

  // 5) La molette de la souris ne doit pas changer un code saisi (champs numériques)
  document.querySelectorAll('input[type=number]').forEach(champ => {
    champ.addEventListener('wheel', () => champ.blur());
  });

  // 6) Les alertes de succès disparaissent après 8 secondes
  document.querySelectorAll('.alert-success.alert-dismissible').forEach(alerte => {
    setTimeout(() => bootstrap.Alert.getOrCreateInstance(alerte).close(), 8000);
  });
});
