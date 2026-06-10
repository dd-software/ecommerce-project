<?php
declare(strict_types=1);

namespace App\Core;

class Auditoria
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function registrar(
        ?string $usuarioId,
        string $modulo,
        string $accion,
        ?string $entidadTipo = null,
        ?string $entidadId = null,
        ?array $detalles = null
    ): void {
        $id = self::generateUuid();
        $ip = Request::ip();
        
        $this->db->execute(
            'INSERT INTO auditoria (id, usuario_id, modulo, accion, entidad_tipo, entidad_id, detalles, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$id, $usuarioId, $modulo, $accion, $entidadTipo, $entidadId, 
             $detalles ? json_encode($detalles) : null, $ip]
        );
    }

    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
