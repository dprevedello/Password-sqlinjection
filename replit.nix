{ pkgs }: {
	deps = [
    pkgs.php82
    pkgs.phpExtensions.mbstring
    pkgs.phpExtensions.pdo
    pkgs.phpExtensions.opcache
    pkgs.phpExtensions.mysqli
    pkgs.mariadb_109
	];
}