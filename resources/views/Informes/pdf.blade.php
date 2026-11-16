<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe de Servicio Técnico #{{ $numero }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 3.3cm 3cm 2.5cm 3cm;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5pt;
            line-height: 1.35;
            color: #111111;
            margin: 0;
        }

        header {
            position: fixed;
            top: -2.4cm;
            left: 0;
            right: 0;
            text-align: center;
        }

        header img {
            height: 1.95cm;
            width: auto;
        }

        footer {
            position: fixed;
            bottom: -1.4cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9.5pt;
            color: #333333;
        }

        .titulo-documento {
            text-align: center;
            font-size: 13.5pt;
            font-weight: bold;
            margin: 0 0 18px 0;
            letter-spacing: 0.2px;
        }

        .metadatos {
            margin-bottom: 18px;
        }

        .metadatos p {
            margin: 0 0 6px 0;
        }

        .tabla-informe {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabla-informe td {
            border: 1px solid #000000;
            padding: 8px 10px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .col-etiqueta {
            width: 35%;
            font-weight: bold;
        }

        .col-valor {
            width: 65%;
        }

        .observaciones-titulo {
            font-weight: bold;
            margin-bottom: 6px;
        }

        .observaciones-texto {
            white-space: pre-line;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>

<body>
    @if ($logoBase64)
        <header>
            <img src="{{ $logoBase64 }}" alt="Secretaría Nacional de Deportes">
        </header>
    @endif

    <footer>
        InfraDesk (SGDI) | Departamento de Informática y TICs
    </footer>

    <main>
        <h1 class="titulo-documento">INFORME DE SERVICIO TÉCNICO</h1>

        <div class="metadatos">
            <p><strong>FECHA:</strong> {{ $fecha }}</p>
            <p><strong>N°:</strong> {{ $numero }}</p>
            <p><strong>USUARIO:</strong> {{ $usuario }}</p>
            <p><strong>TÉCNICO ENCARGADO:</strong> {{ $tecnico }}</p>
        </div>

        <table class="tabla-informe">
            <tbody>
                <tr>
                    <td class="col-etiqueta">Título del problema:</td>
                    <td class="col-valor">{{ $titulo }}</td>
                </tr>
                <tr>
                    <td class="col-etiqueta">Tipo de problema:</td>
                    <td class="col-valor">{{ $tipo_problema }}</td>
                </tr>
                <tr>
                    <td class="col-etiqueta">Detalle del problema:</td>
                    <td class="col-valor">{{ $detalle }}</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="observaciones-titulo">Observaciones:</div>
                        <div class="observaciones-texto">{{ $observacion }}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </main>
</body>

</html>
