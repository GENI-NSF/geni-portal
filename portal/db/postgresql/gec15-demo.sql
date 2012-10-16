--
-- PostgreSQL database dump
--

-- ----------------------------------------------------------------------
-- Dump of the GEC 15 demo database
--
-- Created with:
--
--     $ pg_dump -c -f gec15-demo.sql portal -h localhost -U portal
--
-- Restore with:
--
--     $ psql -U portal -h localhost portal < gec15-demo.sql
-- ----------------------------------------------------------------------

SET statement_timeout = 0;
SET client_encoding = 'SQL_ASCII';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

ALTER TABLE ONLY public.slice DROP CONSTRAINT slice_owner_fkey;
ALTER TABLE ONLY public.outside_key DROP CONSTRAINT outside_key_account_id_fkey;
ALTER TABLE ONLY public.ma_ssh_key DROP CONSTRAINT ma_ssh_key_member_id_fkey;
ALTER TABLE ONLY public.ma_member_privilege DROP CONSTRAINT ma_member_privilege_privilege_id_fkey;
ALTER TABLE ONLY public.ma_member_privilege DROP CONSTRAINT ma_member_privilege_member_id_fkey;
ALTER TABLE ONLY public.ma_member_attribute DROP CONSTRAINT ma_member_attribute_member_id_fkey;
ALTER TABLE ONLY public.ma_inside_key DROP CONSTRAINT ma_inside_key_member_id_fkey;
ALTER TABLE ONLY public.ma_inside_key DROP CONSTRAINT ma_inside_key_client_urn_fkey;
ALTER TABLE ONLY public.identity_attribute DROP CONSTRAINT identity_attribute_identity_id_fkey;
ALTER TABLE ONLY public.identity DROP CONSTRAINT identity_account_id_fkey;
ALTER TABLE ONLY public.account_slice DROP CONSTRAINT account_slice_slice_id_fkey;
ALTER TABLE ONLY public.account_slice DROP CONSTRAINT account_slice_account_id_fkey;
ALTER TABLE ONLY public.account_privilege DROP CONSTRAINT account_privilege_account_id_fkey;
ALTER TABLE ONLY public.abac DROP CONSTRAINT abac_account_id_fkey;
DROP INDEX public.slice_index_owner;
DROP INDEX public.sa_slice_expired;
DROP INDEX public.rspec_visibility;
DROP INDEX public.rspec_schema;
DROP INDEX public.rspec_owner_id;
DROP INDEX public.rspec_name;
DROP INDEX public.outside_key_index_account_id;
DROP INDEX public.ma_ssh_key_member_id;
DROP INDEX public.ma_member_privilege_index_member_id;
DROP INDEX public.ma_member_index_member_id;
DROP INDEX public.ma_member_attribute_index_member_id;
DROP INDEX public.ma_inside_key_index_member_id;
DROP INDEX public.ma_client_index_client_urn;
DROP INDEX public.account_slice_index_slice_id;
DROP INDEX public.account_slice_index_account_id;
DROP INDEX public.account_privilege_index_account_id;
DROP INDEX public.abac_index_account_id;
DROP INDEX public.abac_assertion_subject;
DROP INDEX public.abac_assertion_issuer_role;
DROP INDEX public.abac_assertion_issuer;
ALTER TABLE ONLY public.slice DROP CONSTRAINT slice_urn_key;
ALTER TABLE ONLY public.slice DROP CONSTRAINT slice_pkey;
ALTER TABLE ONLY public.slice DROP CONSTRAINT slice_name_key;
ALTER TABLE ONLY public.shib_attribute DROP CONSTRAINT shib_attribute_pkey;
ALTER TABLE ONLY public.service_registry DROP CONSTRAINT service_registry_pkey;
ALTER TABLE ONLY public.schema_version DROP CONSTRAINT schema_version_pkey;
ALTER TABLE ONLY public.sa_slice DROP CONSTRAINT sa_slice_pkey;
ALTER TABLE ONLY public.sa_slice_member DROP CONSTRAINT sa_slice_member_pkey;
ALTER TABLE ONLY public.rspec DROP CONSTRAINT rspec_pkey;
ALTER TABLE ONLY public.pa_project DROP CONSTRAINT pa_project_pkey;
ALTER TABLE ONLY public.pa_project_member DROP CONSTRAINT pa_project_member_pkey;
ALTER TABLE ONLY public.outside_key DROP CONSTRAINT outside_key_account_id_key;
ALTER TABLE ONLY public.ma_ssh_key DROP CONSTRAINT ma_ssh_key_pkey;
ALTER TABLE ONLY public.ma_privilege DROP CONSTRAINT ma_privilege_pkey;
ALTER TABLE ONLY public.ma_member_privilege DROP CONSTRAINT ma_member_privilege_pkey;
ALTER TABLE ONLY public.ma_member DROP CONSTRAINT ma_member_pkey;
ALTER TABLE ONLY public.ma_member DROP CONSTRAINT ma_member_member_id_key;
ALTER TABLE ONLY public.ma_member_attribute DROP CONSTRAINT ma_member_attribute_pkey;
ALTER TABLE ONLY public.ma_inside_key DROP CONSTRAINT ma_inside_key_pkey;
ALTER TABLE ONLY public.ma_inside_key DROP CONSTRAINT ma_inside_key_client_urn_key;
ALTER TABLE ONLY public.ma_client DROP CONSTRAINT ma_client_pkey;
ALTER TABLE ONLY public.ma_client DROP CONSTRAINT ma_client_client_urn_key;
ALTER TABLE ONLY public.ma_client DROP CONSTRAINT ma_client_client_name_key;
ALTER TABLE ONLY public.logging_entry DROP CONSTRAINT logging_entry_pkey;
ALTER TABLE ONLY public.identity DROP CONSTRAINT identity_pkey;
ALTER TABLE ONLY public.identity DROP CONSTRAINT identity_eppn_key;
ALTER TABLE ONLY public.cs_assertion DROP CONSTRAINT cs_assertion_pkey;
ALTER TABLE ONLY public.account DROP CONSTRAINT account_username_key;
ALTER TABLE ONLY public.account DROP CONSTRAINT account_pkey;
ALTER TABLE public.service_registry ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.sa_slice_member_request ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.sa_slice_member ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.sa_slice ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.rspec ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.pa_project_member_request ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.pa_project_member ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.pa_project ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.ma_ssh_key ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.ma_member_privilege ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.ma_member_attribute ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.ma_member ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.ma_inside_key ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.ma_client ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.logging_entry ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.identity ALTER COLUMN identity_id DROP DEFAULT;
ALTER TABLE public.cs_privilege ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_policy ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_context_type ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_attribute ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_assertion ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_action ALTER COLUMN id DROP DEFAULT;
DROP TABLE public.slice;
DROP TABLE public.shib_attribute;
DROP SEQUENCE public.service_registry_id_seq;
DROP TABLE public.service_registry_attribute;
DROP TABLE public.service_registry;
DROP TABLE public.schema_version;
DROP SEQUENCE public.sa_slice_member_request_id_seq;
DROP TABLE public.sa_slice_member_request;
DROP SEQUENCE public.sa_slice_member_id_seq;
DROP TABLE public.sa_slice_member;
DROP SEQUENCE public.sa_slice_id_seq;
DROP TABLE public.sa_slice;
DROP SEQUENCE public.rspec_id_seq;
DROP TABLE public.rspec;
DROP VIEW public.requested_account;
DROP SEQUENCE public.pa_project_member_request_id_seq;
DROP TABLE public.pa_project_member_request;
DROP SEQUENCE public.pa_project_member_id_seq;
DROP TABLE public.pa_project_member;
DROP SEQUENCE public.pa_project_id_seq;
DROP TABLE public.pa_project;
DROP TABLE public.outside_key;
DROP SEQUENCE public.ma_ssh_key_id_seq;
DROP TABLE public.ma_ssh_key;
DROP TABLE public.ma_privilege;
DROP SEQUENCE public.ma_member_privilege_id_seq;
DROP TABLE public.ma_member_privilege;
DROP SEQUENCE public.ma_member_id_seq;
DROP SEQUENCE public.ma_member_attribute_id_seq;
DROP TABLE public.ma_member_attribute;
DROP TABLE public.ma_member;
DROP SEQUENCE public.ma_inside_key_id_seq;
DROP TABLE public.ma_inside_key;
DROP SEQUENCE public.ma_client_id_seq;
DROP TABLE public.ma_client;
DROP SEQUENCE public.logging_entry_id_seq;
DROP TABLE public.logging_entry_attribute;
DROP TABLE public.logging_entry;
DROP SEQUENCE public.identity_identity_id_seq;
DROP TABLE public.identity_attribute;
DROP TABLE public.identity;
DROP VIEW public.disabled_account;
DROP SEQUENCE public.cs_privilege_id_seq;
DROP TABLE public.cs_privilege;
DROP SEQUENCE public.cs_policy_id_seq;
DROP TABLE public.cs_policy;
DROP SEQUENCE public.cs_context_type_id_seq;
DROP TABLE public.cs_context_type;
DROP SEQUENCE public.cs_attribute_id_seq;
DROP TABLE public.cs_attribute;
DROP SEQUENCE public.cs_assertion_id_seq;
DROP TABLE public.cs_assertion;
DROP SEQUENCE public.cs_action_id_seq;
DROP TABLE public.cs_action;
DROP VIEW public.active_account;
DROP TABLE public.account_slice;
DROP TABLE public.account_privilege;
DROP TABLE public.account;
DROP TABLE public.abac_assertion;
DROP TABLE public.abac;
DROP TYPE public.site_privilege;
DROP TYPE public.rspec_visibility;
DROP TYPE public.account_status;
DROP SCHEMA public;
--
-- Name: public; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA public;


ALTER SCHEMA public OWNER TO postgres;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'standard public schema';


SET search_path = public, pg_catalog;

--
-- Name: account_status; Type: TYPE; Schema: public; Owner: portal
--

CREATE TYPE account_status AS ENUM (
    'requested',
    'active',
    'disabled'
);


ALTER TYPE public.account_status OWNER TO portal;

--
-- Name: rspec_visibility; Type: TYPE; Schema: public; Owner: portal
--

CREATE TYPE rspec_visibility AS ENUM (
    'public',
    'private'
);


ALTER TYPE public.rspec_visibility OWNER TO portal;

--
-- Name: site_privilege; Type: TYPE; Schema: public; Owner: portal
--

CREATE TYPE site_privilege AS ENUM (
    'admin',
    'slice'
);


ALTER TYPE public.site_privilege OWNER TO portal;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: abac; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE abac (
    account_id uuid,
    abac_id character varying,
    abac_key character varying,
    abac_fingerprint character varying
);


ALTER TABLE public.abac OWNER TO portal;

--
-- Name: abac_assertion; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE abac_assertion (
    issuer character varying,
    issuer_role character varying,
    subject character varying,
    expiration timestamp without time zone,
    credential character varying
);


ALTER TABLE public.abac_assertion OWNER TO portal;

--
-- Name: account; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE account (
    account_id uuid NOT NULL,
    status account_status,
    username character varying
);


ALTER TABLE public.account OWNER TO portal;

--
-- Name: account_privilege; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE account_privilege (
    account_id uuid,
    privilege site_privilege
);


ALTER TABLE public.account_privilege OWNER TO portal;

--
-- Name: account_slice; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE account_slice (
    account_id uuid,
    slice_id uuid
);


ALTER TABLE public.account_slice OWNER TO portal;

--
-- Name: active_account; Type: VIEW; Schema: public; Owner: portal
--

CREATE VIEW active_account AS
    SELECT account.account_id, account.status, account.username FROM account WHERE (account.status = 'active'::account_status);


ALTER TABLE public.active_account OWNER TO portal;

--
-- Name: cs_action; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE cs_action (
    id integer NOT NULL,
    name character varying,
    privilege integer,
    context_type integer
);


ALTER TABLE public.cs_action OWNER TO portal;

--
-- Name: cs_action_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE cs_action_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.cs_action_id_seq OWNER TO portal;

--
-- Name: cs_action_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE cs_action_id_seq OWNED BY cs_action.id;


--
-- Name: cs_action_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('cs_action_id_seq', 42, true);


--
-- Name: cs_assertion; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE cs_assertion (
    id integer NOT NULL,
    signer uuid,
    principal uuid,
    attribute integer,
    context_type integer,
    context uuid,
    expiration timestamp without time zone,
    assertion_cert character varying
);


ALTER TABLE public.cs_assertion OWNER TO portal;

--
-- Name: cs_assertion_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE cs_assertion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.cs_assertion_id_seq OWNER TO portal;

--
-- Name: cs_assertion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE cs_assertion_id_seq OWNED BY cs_assertion.id;


--
-- Name: cs_assertion_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('cs_assertion_id_seq', 1, false);


--
-- Name: cs_attribute; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE cs_attribute (
    id integer NOT NULL,
    name character varying
);


ALTER TABLE public.cs_attribute OWNER TO portal;

--
-- Name: cs_attribute_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE cs_attribute_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.cs_attribute_id_seq OWNER TO portal;

--
-- Name: cs_attribute_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE cs_attribute_id_seq OWNED BY cs_attribute.id;


--
-- Name: cs_attribute_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('cs_attribute_id_seq', 1, false);


--
-- Name: cs_context_type; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE cs_context_type (
    id integer NOT NULL,
    name character varying
);


ALTER TABLE public.cs_context_type OWNER TO portal;

--
-- Name: cs_context_type_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE cs_context_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.cs_context_type_id_seq OWNER TO portal;

--
-- Name: cs_context_type_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE cs_context_type_id_seq OWNED BY cs_context_type.id;


--
-- Name: cs_context_type_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('cs_context_type_id_seq', 1, false);


--
-- Name: cs_policy; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE cs_policy (
    id integer NOT NULL,
    signer uuid,
    attribute integer,
    context_type integer,
    privilege integer,
    policy_cert character varying
);


ALTER TABLE public.cs_policy OWNER TO portal;

--
-- Name: cs_policy_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE cs_policy_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.cs_policy_id_seq OWNER TO portal;

--
-- Name: cs_policy_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE cs_policy_id_seq OWNED BY cs_policy.id;


--
-- Name: cs_policy_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('cs_policy_id_seq', 60, true);


--
-- Name: cs_privilege; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE cs_privilege (
    id integer NOT NULL,
    name character varying
);


ALTER TABLE public.cs_privilege OWNER TO portal;

--
-- Name: cs_privilege_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE cs_privilege_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.cs_privilege_id_seq OWNER TO portal;

--
-- Name: cs_privilege_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE cs_privilege_id_seq OWNED BY cs_privilege.id;


--
-- Name: cs_privilege_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('cs_privilege_id_seq', 1, false);


--
-- Name: disabled_account; Type: VIEW; Schema: public; Owner: portal
--

CREATE VIEW disabled_account AS
    SELECT account.account_id, account.status, account.username FROM account WHERE (account.status = 'disabled'::account_status);


ALTER TABLE public.disabled_account OWNER TO portal;

--
-- Name: identity; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE identity (
    identity_id integer NOT NULL,
    provider_url character varying,
    eppn character varying,
    affiliation character varying,
    account_id uuid
);


ALTER TABLE public.identity OWNER TO portal;

--
-- Name: identity_attribute; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE identity_attribute (
    identity_id integer,
    name character varying,
    value character varying,
    self_asserted boolean
);


ALTER TABLE public.identity_attribute OWNER TO portal;

--
-- Name: identity_identity_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE identity_identity_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.identity_identity_id_seq OWNER TO portal;

--
-- Name: identity_identity_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE identity_identity_id_seq OWNED BY identity.identity_id;


--
-- Name: identity_identity_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('identity_identity_id_seq', 1, false);


--
-- Name: logging_entry; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE logging_entry (
    id integer NOT NULL,
    event_time timestamp without time zone,
    user_id uuid,
    message character varying
);


ALTER TABLE public.logging_entry OWNER TO portal;

--
-- Name: logging_entry_attribute; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE logging_entry_attribute (
    event_id integer,
    attribute_name character varying,
    attribute_value character varying
);


ALTER TABLE public.logging_entry_attribute OWNER TO portal;

--
-- Name: logging_entry_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE logging_entry_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.logging_entry_id_seq OWNER TO portal;

--
-- Name: logging_entry_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE logging_entry_id_seq OWNED BY logging_entry.id;


--
-- Name: logging_entry_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('logging_entry_id_seq', 1, false);


--
-- Name: ma_client; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ma_client (
    id integer NOT NULL,
    client_name character varying NOT NULL,
    client_urn character varying NOT NULL
);


ALTER TABLE public.ma_client OWNER TO portal;

--
-- Name: ma_client_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE ma_client_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ma_client_id_seq OWNER TO portal;

--
-- Name: ma_client_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE ma_client_id_seq OWNED BY ma_client.id;


--
-- Name: ma_client_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('ma_client_id_seq', 1, true);


--
-- Name: ma_inside_key; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ma_inside_key (
    id integer NOT NULL,
    client_urn character varying,
    member_id uuid,
    private_key character varying,
    certificate character varying
);


ALTER TABLE public.ma_inside_key OWNER TO portal;

--
-- Name: ma_inside_key_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE ma_inside_key_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ma_inside_key_id_seq OWNER TO portal;

--
-- Name: ma_inside_key_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE ma_inside_key_id_seq OWNED BY ma_inside_key.id;


--
-- Name: ma_inside_key_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('ma_inside_key_id_seq', 1, false);


--
-- Name: ma_member; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ma_member (
    id integer NOT NULL,
    member_id uuid
);


ALTER TABLE public.ma_member OWNER TO portal;

--
-- Name: ma_member_attribute; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ma_member_attribute (
    id integer NOT NULL,
    member_id uuid NOT NULL,
    name character varying NOT NULL,
    value character varying NOT NULL,
    self_asserted boolean NOT NULL
);


ALTER TABLE public.ma_member_attribute OWNER TO portal;

--
-- Name: ma_member_attribute_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE ma_member_attribute_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ma_member_attribute_id_seq OWNER TO portal;

--
-- Name: ma_member_attribute_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE ma_member_attribute_id_seq OWNED BY ma_member_attribute.id;


--
-- Name: ma_member_attribute_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('ma_member_attribute_id_seq', 1, false);


--
-- Name: ma_member_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE ma_member_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ma_member_id_seq OWNER TO portal;

--
-- Name: ma_member_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE ma_member_id_seq OWNED BY ma_member.id;


--
-- Name: ma_member_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('ma_member_id_seq', 1, false);


--
-- Name: ma_member_privilege; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ma_member_privilege (
    id integer NOT NULL,
    member_id uuid NOT NULL,
    privilege_id integer NOT NULL,
    expiration timestamp without time zone
);


ALTER TABLE public.ma_member_privilege OWNER TO portal;

--
-- Name: ma_member_privilege_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE ma_member_privilege_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ma_member_privilege_id_seq OWNER TO portal;

--
-- Name: ma_member_privilege_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE ma_member_privilege_id_seq OWNED BY ma_member_privilege.id;


--
-- Name: ma_member_privilege_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('ma_member_privilege_id_seq', 1, false);


--
-- Name: ma_privilege; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ma_privilege (
    id integer NOT NULL,
    privilege character varying NOT NULL
);


ALTER TABLE public.ma_privilege OWNER TO portal;

--
-- Name: ma_ssh_key; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ma_ssh_key (
    id integer NOT NULL,
    member_id uuid NOT NULL,
    filename character varying,
    description character varying,
    public_key character varying NOT NULL,
    private_key character varying
);


ALTER TABLE public.ma_ssh_key OWNER TO portal;

--
-- Name: ma_ssh_key_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE ma_ssh_key_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ma_ssh_key_id_seq OWNER TO portal;

--
-- Name: ma_ssh_key_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE ma_ssh_key_id_seq OWNED BY ma_ssh_key.id;


--
-- Name: ma_ssh_key_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('ma_ssh_key_id_seq', 1, false);


--
-- Name: outside_key; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE outside_key (
    account_id uuid,
    private_key character varying,
    certificate character varying
);


ALTER TABLE public.outside_key OWNER TO portal;

--
-- Name: pa_project; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE pa_project (
    id integer NOT NULL,
    project_id uuid,
    project_name character varying,
    lead_id uuid,
    project_email character varying,
    project_purpose character varying,
    creation timestamp without time zone
);


ALTER TABLE public.pa_project OWNER TO portal;

--
-- Name: pa_project_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE pa_project_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.pa_project_id_seq OWNER TO portal;

--
-- Name: pa_project_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE pa_project_id_seq OWNED BY pa_project.id;


--
-- Name: pa_project_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('pa_project_id_seq', 1, false);


--
-- Name: pa_project_member; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE pa_project_member (
    id integer NOT NULL,
    project_id uuid,
    member_id uuid,
    role integer
);


ALTER TABLE public.pa_project_member OWNER TO portal;

--
-- Name: pa_project_member_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE pa_project_member_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.pa_project_member_id_seq OWNER TO portal;

--
-- Name: pa_project_member_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE pa_project_member_id_seq OWNED BY pa_project_member.id;


--
-- Name: pa_project_member_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('pa_project_member_id_seq', 1, false);


--
-- Name: pa_project_member_request; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE pa_project_member_request (
    id integer NOT NULL,
    context_type integer,
    context_id uuid,
    request_text character varying,
    request_type integer,
    request_details character varying,
    requestor uuid,
    status integer,
    creation_timestamp timestamp without time zone,
    resolver uuid,
    resolution_timestamp timestamp without time zone,
    resolution_description character varying
);


ALTER TABLE public.pa_project_member_request OWNER TO portal;

--
-- Name: pa_project_member_request_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE pa_project_member_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.pa_project_member_request_id_seq OWNER TO portal;

--
-- Name: pa_project_member_request_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE pa_project_member_request_id_seq OWNED BY pa_project_member_request.id;


--
-- Name: pa_project_member_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('pa_project_member_request_id_seq', 1, false);


--
-- Name: requested_account; Type: VIEW; Schema: public; Owner: portal
--

CREATE VIEW requested_account AS
    SELECT account.account_id, account.status, account.username FROM account WHERE (account.status = 'requested'::account_status);


ALTER TABLE public.requested_account OWNER TO portal;

--
-- Name: rspec; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE rspec (
    id integer NOT NULL,
    name character varying NOT NULL,
    schema character varying NOT NULL,
    schema_version character varying NOT NULL,
    description character varying NOT NULL,
    rspec character varying NOT NULL,
    owner_id uuid,
    visibility rspec_visibility NOT NULL
);


ALTER TABLE public.rspec OWNER TO portal;

--
-- Name: rspec_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE rspec_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.rspec_id_seq OWNER TO portal;

--
-- Name: rspec_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE rspec_id_seq OWNED BY rspec.id;


--
-- Name: rspec_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('rspec_id_seq', 10, true);


--
-- Name: sa_slice; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE sa_slice (
    id integer NOT NULL,
    slice_id uuid,
    owner_id uuid,
    project_id uuid,
    creation timestamp without time zone,
    expiration timestamp without time zone,
    expired boolean DEFAULT false NOT NULL,
    slice_name character varying,
    slice_urn character varying,
    slice_email character varying,
    certificate character varying,
    slice_description character varying
);


ALTER TABLE public.sa_slice OWNER TO portal;

--
-- Name: sa_slice_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE sa_slice_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sa_slice_id_seq OWNER TO portal;

--
-- Name: sa_slice_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE sa_slice_id_seq OWNED BY sa_slice.id;


--
-- Name: sa_slice_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('sa_slice_id_seq', 1, false);


--
-- Name: sa_slice_member; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE sa_slice_member (
    id integer NOT NULL,
    slice_id uuid,
    member_id uuid,
    role integer
);


ALTER TABLE public.sa_slice_member OWNER TO portal;

--
-- Name: sa_slice_member_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE sa_slice_member_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sa_slice_member_id_seq OWNER TO portal;

--
-- Name: sa_slice_member_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE sa_slice_member_id_seq OWNED BY sa_slice_member.id;


--
-- Name: sa_slice_member_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('sa_slice_member_id_seq', 1, false);


--
-- Name: sa_slice_member_request; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE sa_slice_member_request (
    id integer NOT NULL,
    context_type integer,
    context_id uuid,
    request_text character varying,
    request_type integer,
    request_details character varying,
    requestor uuid,
    status integer,
    creation_timestamp timestamp without time zone,
    resolver uuid,
    resolution_timestamp timestamp without time zone,
    resolution_description character varying
);


ALTER TABLE public.sa_slice_member_request OWNER TO portal;

--
-- Name: sa_slice_member_request_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE sa_slice_member_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.sa_slice_member_request_id_seq OWNER TO portal;

--
-- Name: sa_slice_member_request_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE sa_slice_member_request_id_seq OWNED BY sa_slice_member_request.id;


--
-- Name: sa_slice_member_request_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('sa_slice_member_request_id_seq', 1, false);


--
-- Name: schema_version; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE schema_version (
    key character varying(256) NOT NULL,
    installed timestamp without time zone DEFAULT now() NOT NULL,
    extra character varying(256)
);


ALTER TABLE public.schema_version OWNER TO portal;

--
-- Name: service_registry; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE service_registry (
    id integer NOT NULL,
    service_type integer NOT NULL,
    service_url character varying NOT NULL,
    service_cert character varying,
    service_name character varying,
    service_description character varying
);


ALTER TABLE public.service_registry OWNER TO portal;

--
-- Name: service_registry_attribute; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE service_registry_attribute (
    service_id integer,
    attribute_name character varying,
    attribute_value character varying
);


ALTER TABLE public.service_registry_attribute OWNER TO portal;

--
-- Name: service_registry_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE service_registry_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.service_registry_id_seq OWNER TO portal;

--
-- Name: service_registry_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE service_registry_id_seq OWNED BY service_registry.id;


--
-- Name: service_registry_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('service_registry_id_seq', 16, true);


--
-- Name: shib_attribute; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE shib_attribute (
    name character varying NOT NULL
);


ALTER TABLE public.shib_attribute OWNER TO portal;

--
-- Name: slice; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE slice (
    slice_id uuid NOT NULL,
    name character varying,
    expiration timestamp without time zone,
    owner uuid,
    urn character varying NOT NULL
);


ALTER TABLE public.slice OWNER TO portal;

--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY cs_action ALTER COLUMN id SET DEFAULT nextval('cs_action_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY cs_assertion ALTER COLUMN id SET DEFAULT nextval('cs_assertion_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY cs_attribute ALTER COLUMN id SET DEFAULT nextval('cs_attribute_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY cs_context_type ALTER COLUMN id SET DEFAULT nextval('cs_context_type_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY cs_policy ALTER COLUMN id SET DEFAULT nextval('cs_policy_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY cs_privilege ALTER COLUMN id SET DEFAULT nextval('cs_privilege_id_seq'::regclass);


--
-- Name: identity_id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY identity ALTER COLUMN identity_id SET DEFAULT nextval('identity_identity_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY logging_entry ALTER COLUMN id SET DEFAULT nextval('logging_entry_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_client ALTER COLUMN id SET DEFAULT nextval('ma_client_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_inside_key ALTER COLUMN id SET DEFAULT nextval('ma_inside_key_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_member ALTER COLUMN id SET DEFAULT nextval('ma_member_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_member_attribute ALTER COLUMN id SET DEFAULT nextval('ma_member_attribute_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_member_privilege ALTER COLUMN id SET DEFAULT nextval('ma_member_privilege_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_ssh_key ALTER COLUMN id SET DEFAULT nextval('ma_ssh_key_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY pa_project ALTER COLUMN id SET DEFAULT nextval('pa_project_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY pa_project_member ALTER COLUMN id SET DEFAULT nextval('pa_project_member_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY pa_project_member_request ALTER COLUMN id SET DEFAULT nextval('pa_project_member_request_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY rspec ALTER COLUMN id SET DEFAULT nextval('rspec_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY sa_slice ALTER COLUMN id SET DEFAULT nextval('sa_slice_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY sa_slice_member ALTER COLUMN id SET DEFAULT nextval('sa_slice_member_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY sa_slice_member_request ALTER COLUMN id SET DEFAULT nextval('sa_slice_member_request_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY service_registry ALTER COLUMN id SET DEFAULT nextval('service_registry_id_seq'::regclass);


--
-- Data for Name: abac; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY abac (account_id, abac_id, abac_key, abac_fingerprint) FROM stdin;
\.


--
-- Data for Name: abac_assertion; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY abac_assertion (issuer, issuer_role, subject, expiration, credential) FROM stdin;
\.


--
-- Data for Name: account; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY account (account_id, status, username) FROM stdin;
\.


--
-- Data for Name: account_privilege; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY account_privilege (account_id, privilege) FROM stdin;
\.


--
-- Data for Name: account_slice; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY account_slice (account_id, slice_id) FROM stdin;
\.


--
-- Data for Name: cs_action; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY cs_action (id, name, privilege, context_type) FROM stdin;
1	create_assertion	3	5
2	create_policy	3	5
3	renew_assertion	3	5
4	delete_policy	3	5
5	query_assertions	2	5
6	query_policies	2	5
7	create_slice	3	1
8	delete_slice	3	2
9	lookup_slice	2	2
10	lookup_slices	2	1
11	lookup_slice_ids	2	1
12	get_slice_credential	3	2
13	add_slivers	3	2
14	delete_slivers	3	2
15	renew_slice	3	2
16	add_slice_member	3	2
17	remove_slice_member	3	2
18	change_slice_member_role	3	2
19	get_slice_members	2	2
20	get_slices_for_member	2	2
21	lookup_slices_by_ids	2	2
22	get_slice_members_for_project	2	1
23	list_resources	2	2
24	get_services	2	4
25	get_services_of_type	2	4
26	register_service	3	4
27	remove_service	3	4
28	create_project	3	3
29	delete_project	3	1
30	get_projects	2	3
31	get_project_by_lead	2	3
32	lookup_project	2	3
33	update_project	3	1
34	change_lead	3	1
35	add_project_member	3	1
36	remove_project_member	3	1
37	change_member_role	3	1
38	get_project_members	2	1
39	get_projects_for_member	2	1
40	administer_resources	3	3
41	administer_services	3	4
42	administer_members	3	5
\.


--
-- Data for Name: cs_assertion; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY cs_assertion (id, signer, principal, attribute, context_type, context, expiration, assertion_cert) FROM stdin;
\.


--
-- Data for Name: cs_attribute; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY cs_attribute (id, name) FROM stdin;
1	LEAD
2	ADMIN
3	MEMBER
4	AUDITOR
\.


--
-- Data for Name: cs_context_type; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY cs_context_type (id, name) FROM stdin;
1	PROJECT
2	SLICE
3	RESOURCE
4	SERVICE
5	MEMBER
\.


--
-- Data for Name: cs_policy; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY cs_policy (id, signer, attribute, context_type, privilege, policy_cert) FROM stdin;
1	\N	1	1	1	\N
2	\N	1	1	2	\N
3	\N	1	1	3	\N
4	\N	1	2	1	\N
5	\N	1	2	2	\N
6	\N	1	2	3	\N
7	\N	1	3	1	\N
8	\N	1	3	2	\N
9	\N	1	3	3	\N
10	\N	1	4	1	\N
11	\N	1	4	2	\N
12	\N	1	4	3	\N
13	\N	1	5	1	\N
14	\N	1	5	2	\N
15	\N	1	5	3	\N
16	\N	2	1	1	\N
17	\N	2	1	2	\N
18	\N	2	1	3	\N
19	\N	2	2	1	\N
20	\N	2	2	2	\N
21	\N	2	2	3	\N
22	\N	2	3	1	\N
23	\N	2	3	2	\N
24	\N	2	3	3	\N
25	\N	2	4	1	\N
26	\N	2	4	2	\N
27	\N	2	4	3	\N
28	\N	2	5	1	\N
29	\N	2	5	2	\N
30	\N	2	5	3	\N
31	\N	2	1	1	\N
32	\N	2	1	2	\N
33	\N	2	1	3	\N
34	\N	2	2	1	\N
35	\N	2	2	2	\N
36	\N	2	2	3	\N
37	\N	2	3	1	\N
38	\N	2	3	2	\N
39	\N	2	3	3	\N
40	\N	2	4	1	\N
41	\N	2	4	2	\N
42	\N	2	4	3	\N
43	\N	2	5	1	\N
44	\N	2	5	2	\N
45	\N	2	5	3	\N
46	\N	3	1	2	\N
47	\N	3	1	3	\N
48	\N	3	2	2	\N
49	\N	3	2	3	\N
50	\N	3	3	2	\N
51	\N	3	3	3	\N
52	\N	3	4	2	\N
53	\N	3	4	3	\N
54	\N	3	5	2	\N
55	\N	3	5	3	\N
56	\N	4	1	2	\N
57	\N	4	2	2	\N
58	\N	4	3	2	\N
59	\N	4	4	2	\N
60	\N	4	5	2	\N
\.


--
-- Data for Name: cs_privilege; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY cs_privilege (id, name) FROM stdin;
1	DELEGATE
2	READ
3	WRITE
\.


--
-- Data for Name: identity; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY identity (identity_id, provider_url, eppn, affiliation, account_id) FROM stdin;
\.


--
-- Data for Name: identity_attribute; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY identity_attribute (identity_id, name, value, self_asserted) FROM stdin;
\.


--
-- Data for Name: logging_entry; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY logging_entry (id, event_time, user_id, message) FROM stdin;
\.


--
-- Data for Name: logging_entry_attribute; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY logging_entry_attribute (event_id, attribute_name, attribute_value) FROM stdin;
\.


--
-- Data for Name: ma_client; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ma_client (id, client_name, client_urn) FROM stdin;
1	portal	urn:publicid:IDN+panther+authority+portal
\.


--
-- Data for Name: ma_inside_key; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ma_inside_key (id, client_urn, member_id, private_key, certificate) FROM stdin;
\.


--
-- Data for Name: ma_member; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ma_member (id, member_id) FROM stdin;
\.


--
-- Data for Name: ma_member_attribute; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ma_member_attribute (id, member_id, name, value, self_asserted) FROM stdin;
\.


--
-- Data for Name: ma_member_privilege; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ma_member_privilege (id, member_id, privilege_id, expiration) FROM stdin;
\.


--
-- Data for Name: ma_privilege; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ma_privilege (id, privilege) FROM stdin;
1	PROJECT_LEAD
\.


--
-- Data for Name: ma_ssh_key; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ma_ssh_key (id, member_id, filename, description, public_key, private_key) FROM stdin;
\.


--
-- Data for Name: outside_key; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY outside_key (account_id, private_key, certificate) FROM stdin;
\.


--
-- Data for Name: pa_project; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY pa_project (id, project_id, project_name, lead_id, project_email, project_purpose, creation) FROM stdin;
\.


--
-- Data for Name: pa_project_member; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY pa_project_member (id, project_id, member_id, role) FROM stdin;
\.


--
-- Data for Name: pa_project_member_request; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY pa_project_member_request (id, context_type, context_id, request_text, request_type, request_details, requestor, status, creation_timestamp, resolver, resolution_timestamp, resolution_description) FROM stdin;
\.


--
-- Data for Name: rspec; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY rspec (id, name, schema, schema_version, description, rspec, owner_id, visibility) FROM stdin;
1	One virtual machine	GENI	3	Any one virtual machine.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="my-node"\n        exclusive="false">\n    <sliver_type name="emulab-openvz" />\n  </node>\n</rspec>	\N	public
2	One compute node	GENI	3	Any one compute node.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="foo"/>\n</rspec>	\N	public
3	Two compute nodes	GENI	3	Any two compute nodes.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="foo"/>\n  <node client_id="bar"/>\n</rspec>	\N	public
4	Two nodes, one link	GENI	3	Two nodes with a link between them.	<rspec type="request" \n\txmlns="http://www.geni.net/resources/rspec/3" \n\txmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" \n\txsi:schemaLocation="http://www.geni.net/resources/rspec/3 \n\thttp://www.geni.net/resources/rspec/3/request.xsd">  \n  <node client_id="VM-1" >\n    <interface client_id="VM-1:if0"/>\n  </node>\n  <node client_id="VM-2">\n    <interface client_id="VM-2:if0"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="VM-1:if0"/>\n    <interface_ref client_id="VM-2:if0"/>\n    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>\n    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>	\N	public
5	Three nodes, triangle topology	GENI	3	Three nodes in a triangle topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="VM-1">\n    <interface client_id="VM-1:if0"/>\n    <interface client_id="VM-1:if1"/>\n  </node>\n  <node client_id="VM-2">\n    <interface client_id="VM-2:if0"/>\n    <interface client_id="VM-2:if1"/>\n  </node>\n  <node client_id="VM-3">\n    <interface client_id="VM-3:if0"/>\n    <interface client_id="VM-3:if1"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="VM-1:if0"/>\n    <interface_ref client_id="VM-2:if0"/>\n    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>\n    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="VM-2:if1"/>\n    <interface_ref client_id="VM-3:if1"/>\n    <property source_id="VM-2:if1" dest_id="VM-3:if1"/>\n    <property source_id="VM-3:if1" dest_id="VM-2:if1"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan2">\n    <interface_ref client_id="VM-1:if1"/>\n    <interface_ref client_id="VM-3:if0"/>\n    <property source_id="VM-1:if1" dest_id="VM-3:if0"/>\n    <property source_id="VM-3:if0" dest_id="VM-1:if1"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>	\N	public
6	Four nodes, diamond topology	GENI	3	Four nodes in a diamond topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="VM-1">\n    <interface client_id="VM-1:if0"/>\n    <interface client_id="VM-1:if1"/>\n  </node>\n  <node client_id="VM-2">\n    <interface client_id="VM-2:if0"/>\n    <interface client_id="VM-2:if1"/>\n  </node>\n  <node client_id="VM-3">\n    <interface client_id="VM-3:if0"/>\n    <interface client_id="VM-3:if1"/>\n  </node>\n  <node client_id="VM-4">\n    <interface client_id="VM-4:if0"/>\n    <interface client_id="VM-4:if1"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="VM-1:if0"/>\n    <interface_ref client_id="VM-2:if0"/>\n    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>\n    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="VM-2:if1"/>\n    <interface_ref client_id="VM-3:if1"/>\n    <property source_id="VM-2:if1" dest_id="VM-3:if1"/>\n    <property source_id="VM-3:if1" dest_id="VM-2:if1"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan2">\n    <interface_ref client_id="VM-4:if1"/>\n    <interface_ref client_id="VM-3:if0"/>\n    <property source_id="VM-4:if1" dest_id="VM-3:if0"/>\n    <property source_id="VM-3:if0" dest_id="VM-4:if1"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan3">\n    <interface_ref client_id="VM-1:if1"/>\n    <interface_ref client_id="VM-4:if0"/>\n    <property source_id="VM-1:if1" dest_id="VM-4:if0"/>\n    <property source_id="VM-4:if0" dest_id="VM-1:if1"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>	\N	public
7	Three nodes, linear topology	GENI	3	Three nodes in a linear topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="VM-1">\n    <interface client_id="VM-1:if0"/>\n  </node>\n  <node client_id="VM-2">\n    <interface client_id="VM-2:if0"/>\n    <interface client_id="VM-2:if1"/>\n  </node>\n  <node client_id="VM-3">\n    <interface client_id="VM-3:if0"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="VM-1:if0"/>\n    <interface_ref client_id="VM-2:if0"/>\n    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>\n    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="VM-2:if1"/>\n    <interface_ref client_id="VM-3:if0"/>\n    <property source_id="VM-2:if1" dest_id="VM-3:if0"/>\n    <property source_id="VM-3:if0" dest_id="VM-2:if1"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>	\N	public
8	Four nodes, star topology	GENI	3	Four nodes in a star topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="VM-1">\n    <interface client_id="VM-1:if0"/>\n  </node>\n  <node client_id="VM-2">\n    <interface client_id="VM-2:if0"/>\n    <interface client_id="VM-2:if1"/>\n    <interface client_id="VM-2:if2"/>\n  </node>\n  <node client_id="VM-3">\n    <interface client_id="VM-3:if0"/>\n  </node>\n  <node client_id="VM-4">\n    <interface client_id="VM-4:if0"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="VM-1:if0"/>\n    <interface_ref client_id="VM-2:if0"/>\n    <property source_id="VM-1:if0" dest_id="VM-2:if0"/>\n    <property source_id="VM-2:if0" dest_id="VM-1:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="VM-2:if1"/>\n    <interface_ref client_id="VM-3:if0"/>\n    <property source_id="VM-2:if1" dest_id="VM-3:if0"/>\n    <property source_id="VM-3:if0" dest_id="VM-2:if1"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan2">\n    <interface_ref client_id="VM-2:if2"/>\n    <interface_ref client_id="VM-4:if0"/>\n    <property source_id="VM-2:if2" dest_id="VM-4:if0"/>\n    <property source_id="VM-4:if0" dest_id="VM-2:if2"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>	\N	public
9	Click Router Example Experiment	GENI	3	The Click Router Example Experiment topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec type="request" xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.geni.net/resources/rspec/3">\n  <node client_id="top" >\n    <services>\n      <execute command="/local/build-click.sh" shell="sh"/>\n      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>\n    </services>\n    <interface client_id="top:if1"/>\n    <interface client_id="top:if2"/>\n    <interface client_id="top:if3"/>\n  </node>\n  <node client_id="left" >\n    <services>\n      <execute command="/local/build-click.sh" shell="sh"/>\n      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>\n    </services>\n    <interface client_id="left:if1"/>\n    <interface client_id="left:if2"/>\n  </node>\n  <node client_id="right" >\n    <services>\n      <execute command="/local/build-click.sh" shell="sh"/>\n      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>\n    </services>\n    <interface client_id="right:if1"/>\n    <interface client_id="right:if2"/>\n  </node>\n  <node client_id="bottom" >\n    <services>\n      <execute command="/local/build-click.sh" shell="sh"/>\n      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>\n    </services>\n    <interface client_id="bottom:if1"/>\n    <interface client_id="bottom:if2"/>\n    <interface client_id="bottom:if3"/>\n  </node>\n  <node client_id="hostA" >\n    <interface client_id="hostA:if1"/>\n  </node>\n  <node client_id="hostB" >\n    <interface client_id="hostB:if1"/>\n  </node>\n  <link client_id="link-0">\n    <property source_id="top:if1" dest_id="left:if1" capacity="100000"/>\n    <property source_id="left:if1" dest_id="top:if1" capacity="100000"/>\n    <interface_ref client_id="top:if1"/>\n    <interface_ref client_id="left:if1"/>\n  </link>\n  <link client_id="link-1">\n    <property source_id="top:if2" dest_id="right:if1" capacity="100000"/>\n    <property source_id="right:if1" dest_id="top:if2" capacity="100000"/>\n    <interface_ref client_id="top:if2"/>\n    <interface_ref client_id="right:if1"/>\n  </link>\n  <link client_id="link-2">\n    <property source_id="left:if2" dest_id="bottom:if1" capacity="100000"/>\n    <property source_id="bottom:if1" dest_id="left:if2" capacity="100000"/>\n    <interface_ref client_id="left:if2"/>\n    <interface_ref client_id="bottom:if1"/>\n  </link>\n  <link client_id="link-3">\n    <property source_id="right:if2" dest_id="bottom:if2" capacity="100000"/>\n    <property source_id="bottom:if2" dest_id="right:if2" capacity="100000"/>\n    <interface_ref client_id="right:if2"/>\n    <interface_ref client_id="bottom:if2"/>\n  </link>\n  <link client_id="link-A">\n    <property source_id="hostA:if1" dest_id="top:if3" capacity="100000"/>\n    <property source_id="top:if3" dest_id="hostA:if1" capacity="100000"/>\n    <interface_ref client_id="hostA:if1"/>\n    <interface_ref client_id="top:if3"/>\n  </link>\n  <link client_id="link-B">\n    <property source_id="bottom:if3" dest_id="hostB:if1" capacity="100000"/>\n    <property source_id="hostB:if1" dest_id="bottom:if3" capacity="100000"/>\n    <interface_ref client_id="bottom:if3"/>\n    <interface_ref client_id="hostB:if1"/>\n  </link>\n</rspec>	\N	public
10	Two nodes with one delay node	GENI	3	Linear topology with delay node in the middle.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xmlns:delay="http://www.protogeni.net/resources/rspec/ext/delay/1"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd http://www.protogeni.net/resources/rspec/ext/delay/1 http://www.protogeni.net/resources/rspec/ext/delay/1/request-delay.xsd"\n       type="request">\n  <node client_id="PC1">\n    <interface client_id="PC1:if0">\n      <ip address="192.168.2.1" netmask="255.255.255.0" type="ipv4"/>\n    </interface>\n  </node>\n  <node client_id="PC2">\n    <interface client_id="PC2:if0">\n      <ip address="192.168.2.2" netmask="255.255.255.0" type="ipv4"/>\n    </interface>\n  </node>\n  <node client_id="delay">\n    <sliver_type name="delay">\n      <delay:sliver_type_shaping>\n        <delay:pipe source="delay:if0" dest="delay:if1" capacity="1000000" packet_loss="0" latency="1"/>\n        <delay:pipe source="delay:if1" dest="delay:if0" capacity="1000000" packet_loss="0" latency="1"/>\n      </delay:sliver_type_shaping>\n    </sliver_type>\n    <interface client_id="delay:if0"/>\n    <interface client_id="delay:if1"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="delay:if0"/>\n    <interface_ref client_id="PC1:if0"/>\n    <property source_id="delay:if0" dest_id="PC1:if0"/>\n    <property source_id="PC1:if0" dest_id="delay:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="delay:if1"/>\n    <interface_ref client_id="PC2:if0"/>\n    <property source_id="delay:if1" dest_id="PC2:if0"/>\n    <property source_id="PC2:if0" dest_id="delay:if1"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>	\N	public
\.


--
-- Data for Name: sa_slice; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY sa_slice (id, slice_id, owner_id, project_id, creation, expiration, expired, slice_name, slice_urn, slice_email, certificate, slice_description) FROM stdin;
\.


--
-- Data for Name: sa_slice_member; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY sa_slice_member (id, slice_id, member_id, role) FROM stdin;
\.


--
-- Data for Name: sa_slice_member_request; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY sa_slice_member_request (id, context_type, context_id, request_text, request_type, request_details, requestor, status, creation_timestamp, resolver, resolution_timestamp, resolution_description) FROM stdin;
\.


--
-- Data for Name: schema_version; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY schema_version (key, installed, extra) FROM stdin;
003	2012-10-15 15:27:14.561099	schema version
\.


--
-- Data for Name: service_registry; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY service_registry (id, service_type, service_url, service_cert, service_name, service_description) FROM stdin;
1	7		/usr/share/geni-ch/CA/cacert.pem	\N	\N
2	1	https://panther.gpolab.bbn.com/sa/sa_controller.php	/usr/share/geni-ch/sa/sa-cert.pem	\N	\N
3	2	https://panther.gpolab.bbn.com/pa/pa_controller.php	/usr/share/geni-ch/pa/pa-cert.pem	\N	\N
4	3	https://panther.gpolab.bbn.com/ma/ma_controller.php	/usr/share/geni-ch/ma/ma-cert.pem	\N	\N
5	5	https://panther.gpolab.bbn.com/logging/logging_controller.php	/usr/share/geni-ch/logging/logging-cert.pem	\N	\N
6	6	https://panther.gpolab.bbn.com/cs/cs_controller.php	/usr/share/geni-ch/cs/cs-cert.pem	\N	\N
7	8	https://panther.gpolab.bbn.com/secure/kmhome.php	/usr/share/geni-ch/km/km-cert.pem	\N	\N
9	0	https://www.emulab.net:12369/protogeni/xmlrpc/am/2.0	/usr/share/geni-ch/sr/certs/utah-am.pem	ProtoGENI Utah	ProtoGENI Utah AM
10	7		/usr/share/geni-ch/sr/certs/Thawte_Premium_Server_CA.pem		For flack: signer of Utah web server cert
13	0	https://geni.renci.org:11443/orca/xmlrpc	/usr/share/geni-ch/sr/certs/exosm-am.pem	ExoGENI ExoSM	ExoGENI ExoSM
14	7		/usr/share/geni-ch/sr/certs/exosm-am.pem		For Flack: Signer of ExoGENI ExoSM and RENCI rack and BBN rack AM cert (self)
15	0	https://www.utah.geniracks.net:12369/protogeni/xmlrpc/am/2.0	/usr/share/geni-ch/sr/certs/ig-utah-am.pem	InstaGENI Utah	InstaGENI Utah AM
16	7		/usr/share/geni-ch/sr/certs/ig-utah-am.pem		InstaGENI Utah CA (self-signed)
8	100	https://localhost:8001/	/usr/share/geni-ch/portal/gcf.d/am-cert.pem	Local gcf AM	Empty AM
11	100	https://bbn-hn.exogeni.net:11443/orca/xmlrpc	/usr/share/geni-ch/sr/certs/exosm-am.pem	ExoGENI BBN	ExoGENI BBN Rack
12	100	https://rci-hn.exogeni.net:11443/orca/xmlrpc	/usr/share/geni-ch/sr/certs/exosm-am.pem	ExoGENI RENCI	ExoGENI RENCI Rack
\.


--
-- Data for Name: service_registry_attribute; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY service_registry_attribute (service_id, attribute_name, attribute_value) FROM stdin;
\.


--
-- Data for Name: shib_attribute; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY shib_attribute (name) FROM stdin;
\.


--
-- Data for Name: slice; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY slice (slice_id, name, expiration, owner, urn) FROM stdin;
\.


--
-- Name: account_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY account
    ADD CONSTRAINT account_pkey PRIMARY KEY (account_id);


--
-- Name: account_username_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY account
    ADD CONSTRAINT account_username_key UNIQUE (username);


--
-- Name: cs_assertion_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY cs_assertion
    ADD CONSTRAINT cs_assertion_pkey PRIMARY KEY (id);


--
-- Name: identity_eppn_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY identity
    ADD CONSTRAINT identity_eppn_key UNIQUE (eppn);


--
-- Name: identity_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY identity
    ADD CONSTRAINT identity_pkey PRIMARY KEY (identity_id);


--
-- Name: logging_entry_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY logging_entry
    ADD CONSTRAINT logging_entry_pkey PRIMARY KEY (id);


--
-- Name: ma_client_client_name_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_client
    ADD CONSTRAINT ma_client_client_name_key UNIQUE (client_name);


--
-- Name: ma_client_client_urn_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_client
    ADD CONSTRAINT ma_client_client_urn_key UNIQUE (client_urn);


--
-- Name: ma_client_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_client
    ADD CONSTRAINT ma_client_pkey PRIMARY KEY (id);


--
-- Name: ma_inside_key_client_urn_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_inside_key
    ADD CONSTRAINT ma_inside_key_client_urn_key UNIQUE (client_urn, member_id);


--
-- Name: ma_inside_key_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_inside_key
    ADD CONSTRAINT ma_inside_key_pkey PRIMARY KEY (id);


--
-- Name: ma_member_attribute_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_member_attribute
    ADD CONSTRAINT ma_member_attribute_pkey PRIMARY KEY (id);


--
-- Name: ma_member_member_id_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_member
    ADD CONSTRAINT ma_member_member_id_key UNIQUE (member_id);


--
-- Name: ma_member_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_member
    ADD CONSTRAINT ma_member_pkey PRIMARY KEY (id);


--
-- Name: ma_member_privilege_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_member_privilege
    ADD CONSTRAINT ma_member_privilege_pkey PRIMARY KEY (id);


--
-- Name: ma_privilege_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_privilege
    ADD CONSTRAINT ma_privilege_pkey PRIMARY KEY (id);


--
-- Name: ma_ssh_key_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_ssh_key
    ADD CONSTRAINT ma_ssh_key_pkey PRIMARY KEY (id);


--
-- Name: outside_key_account_id_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY outside_key
    ADD CONSTRAINT outside_key_account_id_key UNIQUE (account_id);


--
-- Name: pa_project_member_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY pa_project_member
    ADD CONSTRAINT pa_project_member_pkey PRIMARY KEY (id);


--
-- Name: pa_project_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY pa_project
    ADD CONSTRAINT pa_project_pkey PRIMARY KEY (id);


--
-- Name: rspec_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY rspec
    ADD CONSTRAINT rspec_pkey PRIMARY KEY (id);


--
-- Name: sa_slice_member_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY sa_slice_member
    ADD CONSTRAINT sa_slice_member_pkey PRIMARY KEY (id);


--
-- Name: sa_slice_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY sa_slice
    ADD CONSTRAINT sa_slice_pkey PRIMARY KEY (id);


--
-- Name: schema_version_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY schema_version
    ADD CONSTRAINT schema_version_pkey PRIMARY KEY (key);


--
-- Name: service_registry_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY service_registry
    ADD CONSTRAINT service_registry_pkey PRIMARY KEY (id);


--
-- Name: shib_attribute_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY shib_attribute
    ADD CONSTRAINT shib_attribute_pkey PRIMARY KEY (name);


--
-- Name: slice_name_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY slice
    ADD CONSTRAINT slice_name_key UNIQUE (name);


--
-- Name: slice_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY slice
    ADD CONSTRAINT slice_pkey PRIMARY KEY (slice_id);


--
-- Name: slice_urn_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY slice
    ADD CONSTRAINT slice_urn_key UNIQUE (urn);


--
-- Name: abac_assertion_issuer; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX abac_assertion_issuer ON abac_assertion USING btree (issuer);


--
-- Name: abac_assertion_issuer_role; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX abac_assertion_issuer_role ON abac_assertion USING btree (issuer_role);


--
-- Name: abac_assertion_subject; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX abac_assertion_subject ON abac_assertion USING btree (subject);


--
-- Name: abac_index_account_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX abac_index_account_id ON abac USING btree (account_id);


--
-- Name: account_privilege_index_account_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX account_privilege_index_account_id ON account_privilege USING btree (account_id);


--
-- Name: account_slice_index_account_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX account_slice_index_account_id ON account_slice USING btree (account_id);


--
-- Name: account_slice_index_slice_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX account_slice_index_slice_id ON account_slice USING btree (slice_id);


--
-- Name: ma_client_index_client_urn; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX ma_client_index_client_urn ON ma_client USING btree (client_urn);


--
-- Name: ma_inside_key_index_member_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX ma_inside_key_index_member_id ON ma_inside_key USING btree (member_id);


--
-- Name: ma_member_attribute_index_member_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX ma_member_attribute_index_member_id ON ma_member_attribute USING btree (member_id);


--
-- Name: ma_member_index_member_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX ma_member_index_member_id ON ma_member USING btree (member_id);


--
-- Name: ma_member_privilege_index_member_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX ma_member_privilege_index_member_id ON ma_member_privilege USING btree (member_id);


--
-- Name: ma_ssh_key_member_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX ma_ssh_key_member_id ON ma_ssh_key USING btree (member_id);


--
-- Name: outside_key_index_account_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX outside_key_index_account_id ON outside_key USING btree (account_id);


--
-- Name: rspec_name; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX rspec_name ON rspec USING btree (name);


--
-- Name: rspec_owner_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX rspec_owner_id ON rspec USING btree (owner_id);


--
-- Name: rspec_schema; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX rspec_schema ON rspec USING btree (schema);


--
-- Name: rspec_visibility; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX rspec_visibility ON rspec USING btree (visibility);


--
-- Name: sa_slice_expired; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX sa_slice_expired ON sa_slice USING btree (expired);


--
-- Name: slice_index_owner; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX slice_index_owner ON slice USING btree (owner);


--
-- Name: abac_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY abac
    ADD CONSTRAINT abac_account_id_fkey FOREIGN KEY (account_id) REFERENCES account(account_id);


--
-- Name: account_privilege_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY account_privilege
    ADD CONSTRAINT account_privilege_account_id_fkey FOREIGN KEY (account_id) REFERENCES account(account_id);


--
-- Name: account_slice_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY account_slice
    ADD CONSTRAINT account_slice_account_id_fkey FOREIGN KEY (account_id) REFERENCES account(account_id);


--
-- Name: account_slice_slice_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY account_slice
    ADD CONSTRAINT account_slice_slice_id_fkey FOREIGN KEY (slice_id) REFERENCES slice(slice_id);


--
-- Name: identity_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY identity
    ADD CONSTRAINT identity_account_id_fkey FOREIGN KEY (account_id) REFERENCES account(account_id);


--
-- Name: identity_attribute_identity_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY identity_attribute
    ADD CONSTRAINT identity_attribute_identity_id_fkey FOREIGN KEY (identity_id) REFERENCES identity(identity_id);


--
-- Name: ma_inside_key_client_urn_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_inside_key
    ADD CONSTRAINT ma_inside_key_client_urn_fkey FOREIGN KEY (client_urn) REFERENCES ma_client(client_urn);


--
-- Name: ma_inside_key_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_inside_key
    ADD CONSTRAINT ma_inside_key_member_id_fkey FOREIGN KEY (member_id) REFERENCES ma_member(member_id);


--
-- Name: ma_member_attribute_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_member_attribute
    ADD CONSTRAINT ma_member_attribute_member_id_fkey FOREIGN KEY (member_id) REFERENCES ma_member(member_id);


--
-- Name: ma_member_privilege_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_member_privilege
    ADD CONSTRAINT ma_member_privilege_member_id_fkey FOREIGN KEY (member_id) REFERENCES ma_member(member_id);


--
-- Name: ma_member_privilege_privilege_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_member_privilege
    ADD CONSTRAINT ma_member_privilege_privilege_id_fkey FOREIGN KEY (privilege_id) REFERENCES ma_privilege(id);


--
-- Name: ma_ssh_key_member_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_ssh_key
    ADD CONSTRAINT ma_ssh_key_member_id_fkey FOREIGN KEY (member_id) REFERENCES ma_member(member_id);


--
-- Name: outside_key_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY outside_key
    ADD CONSTRAINT outside_key_account_id_fkey FOREIGN KEY (account_id) REFERENCES account(account_id);


--
-- Name: slice_owner_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY slice
    ADD CONSTRAINT slice_owner_fkey FOREIGN KEY (owner) REFERENCES account(account_id);


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

