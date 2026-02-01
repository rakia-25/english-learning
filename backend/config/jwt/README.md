# Clés JWT

Génère la paire de clés avec :

```bash
php bin/console lexik:jwt:generate-keypair
```

Sous Windows, si OpenSSL échoue (`error:80000003`), exécute la commande depuis un autre environnement (WSL, autre PC) ou installe OpenSSL et assure-toi que PHP a accès à une source d’entropie.

Les fichiers `private.pem` et `public.pem` ne doivent pas être versionnés (sécurité).
