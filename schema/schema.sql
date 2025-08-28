-- Tabla: public.usuario
CREATE TABLE public.usuario (
    id uuid NOT NULL DEFAULT uuid_generate_v4(),
    nombre character varying(50) NOT NULL,
    email character varying(100) NOT NULL,
    fecha_registro timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    activo boolean DEFAULT true,
    CONSTRAINT usuario_pkey PRIMARY KEY (id),
    CONSTRAINT usuario_email_key UNIQUE (email),
    CONSTRAINT usuario_nombre_key UNIQUE (nombre)
);

-- Tabla: public.role
CREATE TABLE public.role (
    id integer NOT NULL DEFAULT nextval('role_id_seq'::regclass),
    name character varying(50) NOT NULL,
    CONSTRAINT role_pkey PRIMARY KEY (id),
    CONSTRAINT role_name_key UNIQUE (name)
);

-- Tabla: public.credenciales
CREATE TABLE public.credenciales (
    id integer NOT NULL DEFAULT nextval('credenciales_id_seq'::regclass),
    usuario_id uuid NOT NULL,
    password text NOT NULL,
    fecha_modificado timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT credenciales_pkey PRIMARY KEY (id),
    CONSTRAINT credenciales_usuario_id_key UNIQUE (usuario_id),
    CONSTRAINT credenciales_usuario_id_fkey FOREIGN KEY (usuario_id)
        REFERENCES public.usuario (id) ON DELETE CASCADE
);

-- Tabla: public.usuario_role
CREATE TABLE public.usuario_role (
    usuario_id uuid NOT NULL,
    role_id integer NOT NULL,
    CONSTRAINT usuario_role_pkey PRIMARY KEY (usuario_id, role_id),
    CONSTRAINT usuario_role_usuario_id_fkey FOREIGN KEY (usuario_id)
        REFERENCES public.usuario (id) ON DELETE CASCADE,
    CONSTRAINT usuario_role_role_id_fkey FOREIGN KEY (role_id)
        REFERENCES public.role (id) ON DELETE CASCADE
);
