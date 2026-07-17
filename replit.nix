{ pkgs }:
let
  php = pkgs.php83.buildEnv {
    extensions = ({ enabled, all }: enabled ++ (with all; [
      bcmath
      gd
      intl
      pdo_mysql
      pdo_pgsql
      zip
    ]));
  };
in
{
  deps = [
    php
    pkgs.php83Packages.composer
    pkgs.nodejs_22
  ];
}
