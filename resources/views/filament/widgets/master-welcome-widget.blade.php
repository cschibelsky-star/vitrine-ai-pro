<x-filament-widgets::widget>
    <div class="vip-master-hero">
        <div class="vip-master-hero__content">
            <div class="vip-master-hero__badge">Vitrine AI Pro Master</div>
            <h1>Centro Operacional Master</h1>
            <p>
                Gestão integrada de clientes, produtos, licenças, módulos, leads, contratos,
                cobranças e indicadores do ecossistema Vitrine AI Pro.
            </p>
            <div class="vip-master-hero__chips">
                <span>Multiempresa</span>
                <span>Multidomínio</span>
                <span>Licenças</span>
                <span>IA e Automação</span>
                <span>Financeiro</span>
            </div>
        </div>
        <div class="vip-master-hero__mark">
            <div class="vip-master-logo-fallback">AI</div>
            <div>
                <strong>Vitrine AI Pro</strong>
                <small>SaaS Operacional</small>
            </div>
        </div>
    </div>

    <style>
        .vip-master-hero {
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            align-items: stretch;
            padding: 1.5rem;
            border-radius: 1.35rem;
            background: linear-gradient(135deg, #0f172a, #1d4ed8 55%, #06b6d4);
            color: #fff;
            box-shadow: 0 20px 50px rgba(15, 23, 42, .22);
            position: relative;
            overflow: hidden;
        }

        .vip-master-hero::before {
            content: "";
            position: absolute;
            right: -6rem;
            top: -6rem;
            width: 18rem;
            height: 18rem;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
        }

        .vip-master-hero__content,
        .vip-master-hero__mark {
            position: relative;
            z-index: 1;
        }

        .vip-master-hero__badge {
            display: inline-flex;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            color: #dbeafe;
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: .8rem;
        }

        .vip-master-hero h1 {
            margin: 0;
            font-size: clamp(1.65rem, 3vw, 2.35rem);
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .vip-master-hero p {
            margin-top: .75rem;
            max-width: 52rem;
            color: #dbeafe;
            font-size: .98rem;
        }

        .vip-master-hero__chips {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-top: 1rem;
        }

        .vip-master-hero__chips span {
            padding: .38rem .7rem;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            color: #eff6ff;
            font-size: .78rem;
            font-weight: 650;
        }

        .vip-master-hero__mark {
            min-width: 12rem;
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
            align-self: center;
        }

        .vip-master-logo-fallback {
            width: 3.15rem;
            height: 3.15rem;
            border-radius: 1rem;
            display: grid;
            place-items: center;
            font-weight: 900;
            color: #0f172a;
            background: linear-gradient(135deg, #ffffff, #93c5fd);
            box-shadow: 0 10px 25px rgba(0,0,0,.18);
        }

        .vip-master-hero__mark strong {
            display: block;
            font-size: .95rem;
            line-height: 1;
        }

        .vip-master-hero__mark small {
            display: block;
            margin-top: .25rem;
            color: #bfdbfe;
            font-size: .76rem;
        }

        @media (max-width: 900px) {
            .vip-master-hero { flex-direction: column; }
            .vip-master-hero__mark { align-self: flex-start; }
        }
    </style>
</x-filament-widgets::widget>
