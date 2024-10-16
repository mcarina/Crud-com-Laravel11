<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Projeto Siges:
- Com docker.
1. ```
   sudo docker-compose up --build -d
   ```

- Dentro do terminal do Docker, terminal do projeto.
2. ```
   composer update ou composer install
   ```
3. ```
   php artisan migrate
   ```
4. ```
    php artisan db:seed
   ```
5. Subir a tabela de escola para o banco de dados, meu repositorio para subir a tabela no banco, com script python:
   ```https://github.com/mcarina/Script-Import-Escolas.git```
6. Acessar a rota de documentação da api:
   ```
    http://host/api/documentation
   ```
7. Acessar a rota de login;

8. Fazer o login com os dados presentes no seed (caminho: database/seeders/UserSeeder.php);

9. Finalizado, caso ocorra tudo bem, já pode acessar as rotas sem problemas.

> [!NOTE]
> As tabelas do banco estarão todas vazias, com exceção da tabela de escolas,
> para preenche-las será necessário baixar a tabela planos de ação que o próprio sistema disponibiliza e preenche-la.
> assim como a tabela de coordenadores, recomendo usar o front-end para facilitar o import e o export das tabelas citadas.
> ```https://github.com/mcarina/front-laravel.git```









## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
