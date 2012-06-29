--
-- PostgreSQL database dump
--

-- ----------------------------------------------------------------------
-- Dump of the GEC 14 demo database
--
-- Created with:
--
--     $ pg_dump -c -f gec14-demo.sql portal -h localhost -U portal
--
-- Restore with:
--
--     $ psql -U portal -h localhost portal < gec14-demo.sql
-- ----------------------------------------------------------------------


SET statement_timeout = 0;
SET client_encoding = 'SQL_ASCII';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

ALTER TABLE ONLY public.ssh_key DROP CONSTRAINT ssh_key_account_id_fkey;
ALTER TABLE ONLY public.slice DROP CONSTRAINT slice_owner_fkey;
ALTER TABLE ONLY public.outside_key DROP CONSTRAINT outside_key_account_id_fkey;
ALTER TABLE ONLY public.inside_key DROP CONSTRAINT inside_key_account_id_fkey;
ALTER TABLE ONLY public.identity_attribute DROP CONSTRAINT identity_attribute_identity_id_fkey;
ALTER TABLE ONLY public.identity DROP CONSTRAINT identity_account_id_fkey;
ALTER TABLE ONLY public.account_slice DROP CONSTRAINT account_slice_slice_id_fkey;
ALTER TABLE ONLY public.account_slice DROP CONSTRAINT account_slice_account_id_fkey;
ALTER TABLE ONLY public.account_privilege DROP CONSTRAINT account_privilege_account_id_fkey;
ALTER TABLE ONLY public.abac DROP CONSTRAINT abac_account_id_fkey;
DROP INDEX public.ssh_key_account_id;
DROP INDEX public.slice_index_owner;
DROP INDEX public.rspec_schema;
DROP INDEX public.rspec_name;
DROP INDEX public.outside_key_index_account_id;
DROP INDEX public.inside_key_index_account_id;
DROP INDEX public.account_slice_index_slice_id;
DROP INDEX public.account_slice_index_account_id;
DROP INDEX public.account_privilege_index_account_id;
DROP INDEX public.abac_index_account_id;
DROP INDEX public.abac_assertion_subject;
DROP INDEX public.abac_assertion_issuer_role;
DROP INDEX public.abac_assertion_issuer;
ALTER TABLE ONLY public.ssh_key DROP CONSTRAINT ssh_key_pkey;
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
ALTER TABLE ONLY public.ma_member DROP CONSTRAINT ma_member_pkey;
ALTER TABLE ONLY public.logging_entry DROP CONSTRAINT logging_entry_pkey;
ALTER TABLE ONLY public.inside_key DROP CONSTRAINT inside_key_account_id_key;
ALTER TABLE ONLY public.identity DROP CONSTRAINT identity_pkey;
ALTER TABLE ONLY public.identity DROP CONSTRAINT identity_eppn_key;
ALTER TABLE ONLY public.cs_assertion DROP CONSTRAINT cs_assertion_pkey;
ALTER TABLE ONLY public.account DROP CONSTRAINT account_username_key;
ALTER TABLE ONLY public.account DROP CONSTRAINT account_pkey;
ALTER TABLE public.ssh_key ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.service_registry ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.sa_slice_member_request ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.sa_slice_member ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.sa_slice ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.rspec ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.pa_project_member_request ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.pa_project_member ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.pa_project ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.ma_member ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.logging_entry_context ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.logging_entry ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.identity ALTER COLUMN identity_id DROP DEFAULT;
ALTER TABLE public.cs_privilege ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_policy ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_context_type ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_attribute ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_assertion ALTER COLUMN id DROP DEFAULT;
ALTER TABLE public.cs_action ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE public.ssh_key_id_seq;
DROP TABLE public.ssh_key;
DROP TABLE public.slice;
DROP TABLE public.shib_attribute;
DROP SEQUENCE public.service_registry_id_seq;
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
DROP SEQUENCE public.ma_member_id_seq;
DROP TABLE public.ma_member;
DROP SEQUENCE public.logging_entry_id_seq;
DROP SEQUENCE public.logging_entry_context_id_seq;
DROP TABLE public.logging_entry_context;
DROP TABLE public.logging_entry;
DROP TABLE public.inside_key;
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

SELECT pg_catalog.setval('cs_assertion_id_seq', 2, true);


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

SELECT pg_catalog.setval('identity_identity_id_seq', 1, true);


--
-- Name: inside_key; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE inside_key (
    account_id uuid,
    private_key character varying,
    certificate character varying
);


ALTER TABLE public.inside_key OWNER TO portal;

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
-- Name: logging_entry_context; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE logging_entry_context (
    id integer NOT NULL,
    context_type integer,
    context_id uuid
);


ALTER TABLE public.logging_entry_context OWNER TO portal;

--
-- Name: logging_entry_context_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE logging_entry_context_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.logging_entry_context_id_seq OWNER TO portal;

--
-- Name: logging_entry_context_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE logging_entry_context_id_seq OWNED BY logging_entry_context.id;


--
-- Name: logging_entry_context_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('logging_entry_context_id_seq', 1, false);


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

SELECT pg_catalog.setval('logging_entry_id_seq', 1, true);


--
-- Name: ma_member; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ma_member (
    id integer NOT NULL,
    member_id uuid,
    role_type integer,
    context_type integer,
    context_id uuid
);


ALTER TABLE public.ma_member OWNER TO portal;

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

SELECT pg_catalog.setval('pa_project_id_seq', 1, true);


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

SELECT pg_catalog.setval('pa_project_member_id_seq', 1, true);


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
    rspec character varying NOT NULL
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

SELECT pg_catalog.setval('rspec_id_seq', 9, true);


--
-- Name: sa_slice; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE sa_slice (
    id integer NOT NULL,
    slice_id uuid,
    slice_name character varying,
    project_id uuid,
    expiration timestamp without time zone,
    owner_id uuid,
    slice_urn character varying,
    slice_email character varying,
    certificate character varying,
    creation timestamp without time zone,
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

SELECT pg_catalog.setval('service_registry_id_seq', 11, true);


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
-- Name: ssh_key; Type: TABLE; Schema: public; Owner: portal; Tablespace: 
--

CREATE TABLE ssh_key (
    id integer NOT NULL,
    account_id uuid NOT NULL,
    filename character varying,
    description character varying,
    public_key character varying NOT NULL,
    private_key character varying
);


ALTER TABLE public.ssh_key OWNER TO portal;

--
-- Name: ssh_key_id_seq; Type: SEQUENCE; Schema: public; Owner: portal
--

CREATE SEQUENCE ssh_key_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.ssh_key_id_seq OWNER TO portal;

--
-- Name: ssh_key_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: portal
--

ALTER SEQUENCE ssh_key_id_seq OWNED BY ssh_key.id;


--
-- Name: ssh_key_id_seq; Type: SEQUENCE SET; Schema: public; Owner: portal
--

SELECT pg_catalog.setval('ssh_key_id_seq', 1, true);


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

ALTER TABLE ONLY logging_entry_context ALTER COLUMN id SET DEFAULT nextval('logging_entry_context_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ma_member ALTER COLUMN id SET DEFAULT nextval('ma_member_id_seq'::regclass);


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
-- Name: id; Type: DEFAULT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ssh_key ALTER COLUMN id SET DEFAULT nextval('ssh_key_id_seq'::regclass);


--
-- Data for Name: abac; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY abac (account_id, abac_id, abac_key, abac_fingerprint) FROM stdin;
d34a65ce-8e39-428e-988d-5b0557ba4b4f	-----BEGIN CERTIFICATE-----\nMIIDBzCCAe+gAwIBAgIIJAtOLh2pJOowDQYJKoZIhvcNAQEFBQAwETEPMA0GA1UE\nAxMGbWJyaW5uMB4XDTEyMDYyNzE4MjAzNVoXDTE1MDYxMjE4MjAzNVowETEPMA0G\nA1UEAxMGbWJyaW5uMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArxT8\nNz1hV+8Gu9JuP+Po5XPGAh9KZAaP96wDwB1WExZ/LotkI+uoUZGVj/OAEnLUpJa6\n/WrYO14lLHSxK/vX7hMbYWLcoVNqQuXOqGd4rRFzup1txa7arA1hhF3XQ7K3+v1r\nQv6I63qkIhjnEm7tDUukWx1YCehbQZevqQukxvYmnPUHe5ZbcPkR74rDLfX+i1Nq\nR+AOdLPoLQgzLmUkgZWnFOuI+8nug5/kMS8KRGVY/4myu6rDX8oeROb3wnNhnUvZ\nOPx8TbnRBXer8fejxz+HiapH7tr4RHdqE5OQS1YxgRlzmkqs/ON+UOz77yAuYGai\nT/P95b72VyNASZ5BKwIDAQABo2MwYTAPBgNVHRMBAf8EBTADAQH/MA4GA1UdDwEB\n/wQEAwIBBjAdBgNVHQ4EFgQU+uVWkAIbC6jDr4Nuwlp8CFBl1HQwHwYDVR0jBBgw\nFoAU+uVWkAIbC6jDr4Nuwlp8CFBl1HQwDQYJKoZIhvcNAQEFBQADggEBAHhSYwhQ\nUW1b902EdQAbUnd6OwOsMvKDQW2h3pSFcwaODcMqhHhioEx3olIwFH+mTQYuu0j0\n+2NnORIA4oJ+edr5RMdd3lV8n9Aar6yzuf5yqgcqeKt6ohBg4sclduXkFnJWgg5i\niPZuzvEHM/lQU+EBDjx8djKHnoO5HouSTiBu7rsw7G6Qj6XUFHGM2EbNjkSuwr3L\ny/NGCwnkSbjy2CZCs8Pj4g2laJ1Yvn7xwZmkj2yB0NXiXirkkY8YNe7IGWjhGt1X\n0KHbVRjuq4P53GDC5EbOBfqAA/JSgSNWVceAXHiFvoPMdmw3ipukaDYkwt1ueI7z\nJCh52BfthE0cRMU=\n-----END CERTIFICATE-----\n	-----BEGIN RSA PRIVATE KEY-----\nMIIEpQIBAAKCAQEArxT8Nz1hV+8Gu9JuP+Po5XPGAh9KZAaP96wDwB1WExZ/Lotk\nI+uoUZGVj/OAEnLUpJa6/WrYO14lLHSxK/vX7hMbYWLcoVNqQuXOqGd4rRFzup1t\nxa7arA1hhF3XQ7K3+v1rQv6I63qkIhjnEm7tDUukWx1YCehbQZevqQukxvYmnPUH\ne5ZbcPkR74rDLfX+i1NqR+AOdLPoLQgzLmUkgZWnFOuI+8nug5/kMS8KRGVY/4my\nu6rDX8oeROb3wnNhnUvZOPx8TbnRBXer8fejxz+HiapH7tr4RHdqE5OQS1YxgRlz\nmkqs/ON+UOz77yAuYGaiT/P95b72VyNASZ5BKwIDAQABAoIBAGxyYS+OM4eWJOOe\nGnA0qYPGzHVwEFIYxoAw6jnLcg9steaMrMCbLa0osi1yNHg47IHuY3CpB7ruO7Rn\nq36FBmEPMLyH0gWTd5RsaC9juBnrX3XOIbp67jP4Ldkhjz79qnwrEI90vjatxpDg\nP/DpCKhdnDZb3LZ2WGAupr3c2nnp5AcgXSeb3XIVUOLjG1lX2Wbf17rdDg5AlYjV\nCubMqqMPWlMZPLEGqvkZOmG7CvR6dfTvMj7QaoiJGW5cK54E1uVVAwWjjc2krmig\nb3ZDvVj40fwldOTFAdZOSTfhhZVF3/5p/l3GoNVadA1ZgAd7wlzcIRZ7y9pp6OId\nXKVtWIECgYEA1CPxXwgRINvX9soEhfZVnKL9vTP8avLw3ipvIlJatDCWfn+Iww3M\nn9WjU2GEapZtaXUNuMpa7KEJrCbP7V/pVsKda2J4ES4Fla0G6MHN6pRtaNqAIV9l\nNje16o4U4mp9WRGjmSGySNgkLJmpMnEvNt2YAy+vS9BUoSgFc0BJoEsCgYEA00ei\nEl91tWSKVx5iuGPdQl8JniCPXhDS3pAliQJV7ecc91pzDrdEWs1KQCWc8n9hgFNy\norAZm7yl0hDWKTWL/dl1YSuzJute8cRwDjqzGnnN01Rx7EaSYpA62RISQ4kd1DVU\nGM3zpyE1KdX0TkWz2/k5g7o2oV7ZFt1STYReFqECgYEAlcYindOWNDrCyQxsESCP\n5Rz2RGS1q9SF1nTjLbozK9C6D+l+5yeQ9q/gtfe9g3tdF/16iKLlevdfWtm0J4V1\nurmAZtaIqqhxbPFSVXavKRVOAZ9yox002giPOWDCwfApO39Gn9C4jNF9CLgcSu66\nRORCdM1v1/joJzeYUXxr/u0CgYEAu1vpwItAseP/vfPnLYb5njLzL3PGT29x4AZK\nEu6sTvVFPaDLKYChsDgeoTX5sR3+07KslNK/Utj+34Mot5CRnUIrEkmTbG8LWjCO\nAkBRtafQO5jGEfYfOCKY3Qhmg9djSV0lP33blRkgsQHXzVWIgwjG1Sbb7UxUDS+l\nHbLFUSECgYEAz9eZkw41+MmwU5yfXIj+6FGslrF1c3nnawFn4KHHqugOwo2EOk6e\nWBcfwYr8LSnbJssuo3vBOuPIFx4NC3Ov4cDMuLiBdLAnwuiYt6cj2+4FmNodvPZR\ndimmFxamc3GNBjfUC/cXG9zSNcHOLnyWD8D6TCebHw9T61KRmkNiZ9g=\n-----END RSA PRIVATE KEY-----\n	fae55690021b0ba8c3af836ec25a7c085065d474
\.


--
-- Data for Name: abac_assertion; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY abac_assertion (issuer, issuer_role, subject, expiration, credential) FROM stdin;
\N	staff_at_gpolab_dot_bbn_dot_com	fae55690021b0ba8c3af836ec25a7c085065d474	2012-06-27 14:20:35	MIICljCCAX4CAQEwQqAlMBmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsAgg09k3qOZK1GqEZpBcwFTETMBEGA1UEAxMKR2VuaVBvcnRhbKAbMBmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsMA0GCSqGSIb3DQEBBQUAAgh97jQcLZDoMjAiGA8yMDEyMDYyNzE4MjAzNVoYDzIwMTIwNzA0MTgyMDM1WjCBiTCBhgYIKwYBBQUHCgQxejB4MHYMdGNhMjk5YTBiZjE2Nzk3MzhkZWYxMzMyOGQ1ZTAwODY5YWY0MzhkNGQuc3RhZmZfYXRfZ3BvbGFiX2RvdF9iYm5fZG90X2NvbSA8LSBmYWU1NTY5MDAyMWIwYmE4YzNhZjgzNmVjMjVhN2MwODUwNjVkNDc0ME8wQgYDVR0jBDswOcopmgvxZ5c43vEzKNXgCGmvQ41NoRmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsggg09k3qOZK1GjAJBgNVHTgEAgUAMA0GCSqGSIb3DQEBBQUAA4IBAQAdKWXgrb8KWqo5RflvMGBYaneUk8onPZUSJb7FhZsdj9dYQKTxtqnQU0Am1GNw7xDqW9W9FurlW2R6IhjddkfeyIy7s01p4DTuOOjwwUtJnp2bo2CSZx1QztpsgAF6vul5v0HTIEm+dsyzwT9lQauOS9uPNUlrBW4UUUMOvhx14+f5ZM1eHfgcL2kiJWipvEQMYEDIpOw+3c166qYI9eIwAhJKNPO1mWgr0DkOYlENbQlihCNk+33xiNbbps1sEBC3NkPOovaqTYF3z+K6PevSaujfBlleZ5723K4LIueqG5Y/UQz3C7s/txOUngMRZYBPIooGw8NOGOQQwjol+7Rd
\N	member_at_gpolab_dot_bbn_dot_com	fae55690021b0ba8c3af836ec25a7c085065d474	2012-06-27 14:20:35	MIIClzCCAX8CAQEwQqAlMBmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsAgg09k3qOZK1GqEZpBcwFTETMBEGA1UEAxMKR2VuaVBvcnRhbKAbMBmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsMA0GCSqGSIb3DQEBBQUAAghfCRZ8rBa42jAiGA8yMDEyMDYyNzE4MjAzNVoYDzIwMTIwNzA0MTgyMDM1WjCBijCBhwYIKwYBBQUHCgQxezB5MHcMdWNhMjk5YTBiZjE2Nzk3MzhkZWYxMzMyOGQ1ZTAwODY5YWY0MzhkNGQubWVtYmVyX2F0X2dwb2xhYl9kb3RfYmJuX2RvdF9jb20gPC0gZmFlNTU2OTAwMjFiMGJhOGMzYWY4MzZlYzI1YTdjMDg1MDY1ZDQ3NDBPMEIGA1UdIwQ7MDnKKZoL8WeXON7xMyjV4Ahpr0ONTaEZpBcwFTETMBEGA1UEAxMKR2VuaVBvcnRhbIIINPZN6jmStRowCQYDVR04BAIFADANBgkqhkiG9w0BAQUFAAOCAQEAZCmjfv2ht8K/bEPlBE+ycoJlgM/7dddFY32ksupqamKLzvAq5HBTUzt3pCx+dJcS2eAWjxQ+YgE1xvQiET7bssAChP/xkw5jE2bU7qKE/wdAuQKF+KT/+aLZQrAy01bRGbdDD09Dv3U4vKTyoi9D4aGVouoRfDPuN+NCQeWU+PQwpRJu3blLslKQbxIY3C9KBAwvh00lb5oqPhKw0+KOfEV0flPjYlGtQ4pMHZoNEWJpfSIpfCASvhC/VkouQjcDH7y7Tt/aO8B6YHYjdGu+5f9JWbU1wTm427PRIrEVNZO9NX5bw/7rx77MD/Sjoc1gMeseI7My+B/hD0qj8zTJ/w==
\N	staff_at_gpolab_dot_bbn_dot_com	fae55690021b0ba8c3af836ec25a7c085065d474	2012-06-27 14:20:52	MIICljCCAX4CAQEwQqAlMBmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsAgg09k3qOZK1GqEZpBcwFTETMBEGA1UEAxMKR2VuaVBvcnRhbKAbMBmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsMA0GCSqGSIb3DQEBBQUAAgh2dq3lnH1MdzAiGA8yMDEyMDYyNzE4MjA1MloYDzIwMTIwNzA0MTgyMDUyWjCBiTCBhgYIKwYBBQUHCgQxejB4MHYMdGNhMjk5YTBiZjE2Nzk3MzhkZWYxMzMyOGQ1ZTAwODY5YWY0MzhkNGQuc3RhZmZfYXRfZ3BvbGFiX2RvdF9iYm5fZG90X2NvbSA8LSBmYWU1NTY5MDAyMWIwYmE4YzNhZjgzNmVjMjVhN2MwODUwNjVkNDc0ME8wQgYDVR0jBDswOcopmgvxZ5c43vEzKNXgCGmvQ41NoRmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsggg09k3qOZK1GjAJBgNVHTgEAgUAMA0GCSqGSIb3DQEBBQUAA4IBAQBnUPxL7N9F9JbtlOYTR0vwYXuGoWFjO+XxDay+ivJIF6aNLqUuya8OPL8HMw1KsDXnA1i5z40OV0ZpQYX1flwhvQNJQjw/vvyw81+PmX1IiJScsG0vbs2KUPgf/Ns/y+D4utfyjQd9wxvcd5crb5WmRIMVLvVibq8kfvv+7AtKWMCsH542rSOVxe79KjWlFIzbVYPi2/Jq2ykAWPcfa6TwRj1+7QfHCgSJjfHZwu19QKmcJQrx+1kfq7Jwopq9C5qw6Nq/VAQCTL9NXOZgCQBDQZ2h9mISkpYcdeiGdIdhOBZbJrGoKvBJB1H1L7d+o51xIVSEnFhwU57caOovt4mJ
\N	member_at_gpolab_dot_bbn_dot_com	fae55690021b0ba8c3af836ec25a7c085065d474	2012-06-27 14:20:52	MIIClzCCAX8CAQEwQqAlMBmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsAgg09k3qOZK1GqEZpBcwFTETMBEGA1UEAxMKR2VuaVBvcnRhbKAbMBmkFzAVMRMwEQYDVQQDEwpHZW5pUG9ydGFsMA0GCSqGSIb3DQEBBQUAAgiiQtfkdL4doDAiGA8yMDEyMDYyNzE4MjA1MloYDzIwMTIwNzA0MTgyMDUyWjCBijCBhwYIKwYBBQUHCgQxezB5MHcMdWNhMjk5YTBiZjE2Nzk3MzhkZWYxMzMyOGQ1ZTAwODY5YWY0MzhkNGQubWVtYmVyX2F0X2dwb2xhYl9kb3RfYmJuX2RvdF9jb20gPC0gZmFlNTU2OTAwMjFiMGJhOGMzYWY4MzZlYzI1YTdjMDg1MDY1ZDQ3NDBPMEIGA1UdIwQ7MDnKKZoL8WeXON7xMyjV4Ahpr0ONTaEZpBcwFTETMBEGA1UEAxMKR2VuaVBvcnRhbIIINPZN6jmStRowCQYDVR04BAIFADANBgkqhkiG9w0BAQUFAAOCAQEAGHZK2qVSRn/TrhPIYefw0WlooJVYnkjFNVYt0Ul0NlLjeN7DG7Es2BIetljEOV+FsdxU5qXjORSVhV3vf67FIzKhARU/tAgtkEzWmD5dg7pb6P/DeYQDLqWb1JjsvUW9FdtK88/u+WAn9u4aYqW5Bh04d6dS3OaJcy8+vbFvIezdx2l4AcUd5/rZ6WXr8+D2xxKlk+aniN1I3+dtFW+GKZ9sapPrpD9vTolaThFlMKEf/gptUHcUUlu2WqaMx/OoU/F9k2G4t65YONaz3mWhkooothtoJ5p4OGqf3EW7fdJyXAw5ao2WNgEKlxpY7enshD54hdw/SxqEbSDMnTAaag==
\.


--
-- Data for Name: account; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY account (account_id, status, username) FROM stdin;
d34a65ce-8e39-428e-988d-5b0557ba4b4f	active	mbrinn
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
1	\N	d34a65ce-8e39-428e-988d-5b0557ba4b4f	1	3	\N	\N	\N
2	\N	d34a65ce-8e39-428e-988d-5b0557ba4b4f	1	1	05435984-2d7e-40ce-a2d9-918849f99534	2012-07-27 14:21:25	
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
1	https://cetaganda.gpolab.bbn.com:8444/idp/shibboleth	mbrinn@gpolab.bbn.com	staff@gpolab.bbn.com;member@gpolab.bbn.com	d34a65ce-8e39-428e-988d-5b0557ba4b4f
\.


--
-- Data for Name: identity_attribute; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY identity_attribute (identity_id, name, value, self_asserted) FROM stdin;
1	givenName	Marshall	f
1	sn	Brinn	f
1	mail	mbrinn@geni.net	f
1	telephoneNumber	\N	t
1	reference	\N	t
1	reason	\N	t
1	profile	\N	t
\.


--
-- Data for Name: inside_key; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY inside_key (account_id, private_key, certificate) FROM stdin;
d34a65ce-8e39-428e-988d-5b0557ba4b4f	-----BEGIN RSA PRIVATE KEY-----\nMIICXQIBAAKBgQDBt1nXGEr87/fFTOnx4KWK4BtrE8O8lGX1Em/KqryYLozP+7D9\nMoaAjDmZq2CCcA+0i6p29iNliw29ILGw6FbMuyZEYRKyKlHhtusFeQKiH4aZYbVm\ng6AoQDRntCnYA2mrpDwAgilNCYA+w3qWFsWFYnSp3quUrRF+M8KAZUUghwIDAQAB\nAoGACSqA0MGwgqlkIuzRwQUtMBYMlhN4Vor7DA6URZWwmM7TEOBK7qAeZyS7cP7/\n8QwWYiedqEVbRWm/+6v5XHKR8MbafxQr/eDn5GuoKHypb8/E8v7qC/sV9r8HtAdU\nZC9yQ5/fyxLyULbLGUL09wwHSnvtFwVSltv2L0+j2fAZ+BECQQD1COuRr8Oak5I0\nNwFvEt++GA2hRIPi4lSDXzz/EwepGdEoLxQCk+Xq+PnDYdFZEpZtGqZD8XlZN5I1\nMVnuc+JfAkEAymKIY3uHYJ+9GNI3+usDvxFcn+lB94KmVVr7ZrXvfTH2iqDIR1UJ\n6CQI+qElvhV16pJ8Q6oop+M6WkVmh6qC2QJBAM4KxisnJM+iL1qAtk/0CvgJucxV\nlKD/uBkPyHakRdjHwLyNecIBI2BGI59LbYH8w0jTE+Ql9Og0dlkMOpbsEvkCQEjK\n7+t4PtUOH2GSGvhxF0Pd5sbNiyCPKWyB8PKcdas+EUMDWYXunEWW0HP1CjI4XdUl\nIvTjewQHwrE9TQd22OECQQDHc40MVQ7Xz1MOiBK5UfnNorREM7Y8GpdC6HneDNvl\n95QvQQqdq0CABs4s/WSstbiCBkl05dimhubbqvHM6WQw\n-----END RSA PRIVATE KEY-----\n	-----BEGIN CERTIFICATE-----\nMIIDQjCCAqugAwIBAgIBEDANBgkqhkiG9w0BAQUFADCBkjEQMA4GA1UEChMHcGFu\ndGhlcjESMBAGA1UECxMJYXV0aG9yaXR5MQswCQYDVQQLEwJtYTEtMCsGA1UEAxMk\nZTk0MGMwNjYtYmFkZS0xMWUxLWJjMGMtMDAwYzI5MzM3Mzk2MS4wLAYJKoZIhvcN\nAQkBFh9wb3J0YWwtZGV2LWFkbWluQGdwb2xhYi5iYm4uY29tMB4XDTEyMDYyNzE4\nMjAzNVoXDTEzMDYyNzE4MjAzNVowTzEtMCsGA1UEAxMkZDM0YTY1Y2UtOGUzOS00\nMjhlLTk4OGQtNWIwNTU3YmE0YjRmMR4wHAYJKoZIhvcNAQkBFg9tYnJpbm5AZ2Vu\naS5uZXQwgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMG3WdcYSvzv98VM6fHg\npYrgG2sTw7yUZfUSb8qqvJgujM/7sP0yhoCMOZmrYIJwD7SLqnb2I2WLDb0gsbDo\nVsy7JkRhErIqUeG26wV5AqIfhplhtWaDoChANGe0KdgDaaukPACCKU0JgD7DepYW\nxYVidKneq5StEX4zwoBlRSCHAgMBAAGjgekwgeYwHQYDVR0OBBYEFO6Vwahg+bC/\nNb8BbCFiny7zjXgvMEkGA1UdIwRCMECAFCTANyFqkUz1gyHHCAg2BKZROTp1oSWk\nIzAhMR8wHQYDVQQDExZwYW50aGVyLmdwb2xhYi5iYm4uY29tggEDMAkGA1UdEwQC\nMAAwbwYDVR0RBGgwZoEPbWJyaW5uQGdlbmkubmV0hiR1cm46cHVibGljaWQ6SURO\nK3BhbnRoZXIrdXNlcittYnJpbm6GLXVybjp1dWlkOmQzNGE2NWNlLThlMzktNDI4\nZS05ODhkLTViMDU1N2JhNGI0ZjANBgkqhkiG9w0BAQUFAAOBgQBrsHCc8i6eGVEw\nM2gBjAVwtlV4gAYqxR5lM/u+2fNar5JZpYiKdTZPkd21gJ/XvJwei8vHXZjj5R/m\nSnt67FOUHjDH/4j+Nt0tU7OfO7CdzP+9ASY3OkZwq3owuy2YZq7pPdraw9IOMTHD\n+FfdJlG2uNMjJpRSeCet9oIq8BAStA==\n-----END CERTIFICATE-----\n-----BEGIN CERTIFICATE-----\nMIIDejCCAuOgAwIBAgIBAzANBgkqhkiG9w0BAQUFADAhMR8wHQYDVQQDExZwYW50\naGVyLmdwb2xhYi5iYm4uY29tMB4XDTEyMDYyMDEzNTAzNVoXDTEzMDYyMDEzNTAz\nNVowgZIxEDAOBgNVBAoTB3BhbnRoZXIxEjAQBgNVBAsTCWF1dGhvcml0eTELMAkG\nA1UECxMCbWExLTArBgNVBAMTJGU5NDBjMDY2LWJhZGUtMTFlMS1iYzBjLTAwMGMy\nOTMzNzM5NjEuMCwGCSqGSIb3DQEJARYfcG9ydGFsLWRldi1hZG1pbkBncG9sYWIu\nYmJuLmNvbTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAkUywR41Vdo/INAvG\nE6HG3MXB5VtKvZK1tQZefPcshac16E8XNjumWtyMLTaMjhrgmvsn4+zEmmVPmj4D\n40v0eZXcz+FUu5xCsVi43HRqPNX4hCnhNI8b1mAIDfvkXTrh9DxDH55HHGpNVl6X\nPVsYAM8mebf2ShIa/oPLDnPQakkCAwEAAaOCAU4wggFKMB0GA1UdDgQWBBQkwDch\napFM9YMhxwgINgSmUTk6dTBRBgNVHSMESjBIgBR6Xnq76IUGWDt6lGJQc/rytL6E\nRKElpCMwITEfMB0GA1UEAxMWcGFudGhlci5ncG9sYWIuYmJuLmNvbYIJAKt2ReLP\nD6BQMEoGCCsGAQUFBwEBBD4wPDA6BhRpg8yTgKiYzKjHvbGngICqrteKG4YiaHR0\ncHM6Ly9leGFtcGxlLmdlbmkubmV0L2luZm8uaHRtbDB8BgNVHREEdTBzgR9wb3J0\nYWwtZGV2LWFkbWluQGdwb2xhYi5iYm4uY29thiV1cm46cHVibGljaWQ6SUROK3Bh\nbnRoZXIrYXV0aG9yaXR5K21hhil1dWlkOmU5NDBjMDY2LWJhZGUtMTFlMS1iYzBj\nLTAwMGMyOTMzNzM5NjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAG1Q\n7kx3Aormi5aa0UyymZUgpfiLfP9r/zBZUki7gr3nfQOGYZgYZj5bMVZ+aduOdC16\nzzzS87IKgpZUpbK++epX2paTl5lbwWTpjpwex2s6jETtDobsrC5m5AMXU3Ymko7E\nqD+9hUtDL4MwwWrNPjTP7PX9SGTLL6poh3Y8izuH\n-----END CERTIFICATE-----\n
\.


--
-- Data for Name: logging_entry; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY logging_entry (id, event_time, user_id, message) FROM stdin;
1	2012-06-27 14:21:25	d34a65ce-8e39-428e-988d-5b0557ba4b4f	Created project: CS101
\.


--
-- Data for Name: logging_entry_context; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY logging_entry_context (id, context_type, context_id) FROM stdin;
1	1	05435984-2d7e-40ce-a2d9-918849f99534
\.


--
-- Data for Name: ma_member; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ma_member (id, member_id, role_type, context_type, context_id) FROM stdin;
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
1	05435984-2d7e-40ce-a2d9-918849f99534	CS101	d34a65ce-8e39-428e-988d-5b0557ba4b4f	project-CS101@example.com	teach CS	2012-06-27 14:21:25
\.


--
-- Data for Name: pa_project_member; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY pa_project_member (id, project_id, member_id, role) FROM stdin;
1	05435984-2d7e-40ce-a2d9-918849f99534	d34a65ce-8e39-428e-988d-5b0557ba4b4f	1
\.


--
-- Data for Name: pa_project_member_request; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY pa_project_member_request (id, context_type, context_id, request_text, request_type, request_details, requestor, status, creation_timestamp, resolver, resolution_timestamp, resolution_description) FROM stdin;
\.


--
-- Data for Name: rspec; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY rspec (id, name, schema, schema_version, description, rspec) FROM stdin;
1	One compute node	GENI	3	Any one compute node.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="foo">\n    <hardware_type name="pc600"/>\n  </node>\n</rspec>
2	Two compute nodes	GENI	3	Any two compute nodes.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="foo">\n    <hardware_type name="pc600"/>\n  </node>\n  <node client_id="bar">\n    <hardware_type name="pc600"/>\n  </node>\n</rspec>
3	Two nodes, one link	GENI	3	Two nodes with a link between them.	<rspec type="request"\n\txmlns="http://www.geni.net/resources/rspec/3"\n\txmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n\txsi:schemaLocation="http://www.geni.net/resources/rspec/3\n\thttp://www.geni.net/resources/rspec/3/request.xsd">\n  <node client_id="E1">\n    <hardware_type name="pc600"/>\n    <interface client_id="E1:if0"/>\n  </node>\n  <node client_id="E2">\n    <hardware_type name="pc600"/>\n    <interface client_id="E2:if0"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="E1:if0"/>\n    <interface_ref client_id="E2:if0"/>\n    <property source_id="E1:if0" dest_id="E2:if0"/>\n    <property source_id="E2:if0" dest_id="E1:if0"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>
4	Three nodes, triangle topology	GENI	3	Three nodes in a triangle topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="E1">\n    <hardware_type name="pc600"/>\n    <interface client_id="E1:if0"/>\n    <interface client_id="E1:if1"/>\n  </node>\n  <node client_id="E2">\n    <hardware_type name="pc600"/>\n    <interface client_id="E2:if0"/>\n    <interface client_id="E2:if1"/>\n  </node>\n  <node client_id="E3">\n    <hardware_type name="pc600"/>\n    <interface client_id="E3:if0"/>\n    <interface client_id="E3:if1"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="E1:if0"/>\n    <interface_ref client_id="E2:if0"/>\n    <property source_id="E1:if0" dest_id="E2:if0"/>\n    <property source_id="E2:if0" dest_id="E1:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="E2:if1"/>\n    <interface_ref client_id="E3:if1"/>\n    <property source_id="E2:if1" dest_id="E3:if1"/>\n    <property source_id="E3:if1" dest_id="E2:if1"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan2">\n    <interface_ref client_id="E1:if1"/>\n    <interface_ref client_id="E3:if0"/>\n    <property source_id="E1:if1" dest_id="E3:if0"/>\n    <property source_id="E3:if0" dest_id="E1:if1"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>
5	Four nodes, diamond topology	GENI	3	Four nodes in a diamond topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="E1">\n    <hardware_type name="pc600"/>\n    <interface client_id="E1:if0"/>\n    <interface client_id="E1:if1"/>\n  </node>\n  <node client_id="E2">\n    <hardware_type name="pc600"/>\n    <interface client_id="E2:if0"/>\n    <interface client_id="E2:if1"/>\n  </node>\n  <node client_id="E3">\n    <hardware_type name="pc600"/>\n    <interface client_id="E3:if0"/>\n    <interface client_id="E3:if1"/>\n  </node>\n  <node client_id="E4">\n    <hardware_type name="pc600"/>\n    <interface client_id="E4:if0"/>\n    <interface client_id="E4:if1"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="E1:if0"/>\n    <interface_ref client_id="E2:if0"/>\n    <property source_id="E1:if0" dest_id="E2:if0"/>\n    <property source_id="E2:if0" dest_id="E1:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="E2:if1"/>\n    <interface_ref client_id="E3:if1"/>\n    <property source_id="E2:if1" dest_id="E3:if1"/>\n    <property source_id="E3:if1" dest_id="E2:if1"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan2">\n    <interface_ref client_id="E4:if1"/>\n    <interface_ref client_id="E3:if0"/>\n    <property source_id="E4:if1" dest_id="E3:if0"/>\n    <property source_id="E3:if0" dest_id="E4:if1"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan3">\n    <interface_ref client_id="E1:if1"/>\n    <interface_ref client_id="E4:if0"/>\n    <property source_id="E1:if1" dest_id="E4:if0"/>\n    <property source_id="E4:if0" dest_id="E1:if1"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>
6	Three nodes, linear topology	GENI	3	Three nodes in a linear topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="E1">\n    <hardware_type name="pc600"/>\n    <interface client_id="E1:if0"/>\n  </node>\n  <node client_id="E2">\n    <hardware_type name="pc600"/>\n    <interface client_id="E2:if0"/>\n    <interface client_id="E2:if1"/>\n  </node>\n  <node client_id="E3">\n    <hardware_type name="pc600"/>\n    <interface client_id="E3:if0"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="E1:if0"/>\n    <interface_ref client_id="E2:if0"/>\n    <property source_id="E1:if0" dest_id="E2:if0"/>\n    <property source_id="E2:if0" dest_id="E1:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="E2:if1"/>\n    <interface_ref client_id="E3:if0"/>\n    <property source_id="E2:if1" dest_id="E3:if0"/>\n    <property source_id="E3:if0" dest_id="E2:if1"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>
7	Four nodes, star topology	GENI	3	Four nodes in a star topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd"\n       type="request">\n  <node client_id="E1">\n    <hardware_type name="pc600"/>\n    <interface client_id="E1:if0"/>\n  </node>\n  <node client_id="E2">\n    <hardware_type name="pc600"/>\n    <interface client_id="E2:if0"/>\n    <interface client_id="E2:if1"/>\n    <interface client_id="E2:if2"/>\n  </node>\n  <node client_id="E3">\n    <hardware_type name="pc600"/>\n    <interface client_id="E3:if0"/>\n  </node>\n  <node client_id="E4">\n    <hardware_type name="pc600"/>\n    <interface client_id="E4:if0"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="E1:if0"/>\n    <interface_ref client_id="E2:if0"/>\n    <property source_id="E1:if0" dest_id="E2:if0"/>\n    <property source_id="E2:if0" dest_id="E1:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="E2:if1"/>\n    <interface_ref client_id="E3:if0"/>\n    <property source_id="E2:if1" dest_id="E3:if0"/>\n    <property source_id="E3:if0" dest_id="E2:if1"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan2">\n    <interface_ref client_id="E2:if2"/>\n    <interface_ref client_id="E4:if0"/>\n    <property source_id="E2:if2" dest_id="E4:if0"/>\n    <property source_id="E4:if0" dest_id="E2:if2"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>
8	Click Router Example Experiment	GENI	3	The Click Router Example Experiment topology.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec type="request" xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.geni.net/resources/rspec/3">\n  <node client_id="top" >\n    <services>\n      <execute command="/local/build-click.sh" shell="sh"/>\n      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>\n    </services>\n    <hardware_type name="pc600"/>\n    <interface client_id="top:if1"/>\n    <interface client_id="top:if2"/>\n    <interface client_id="top:if3"/>\n  </node>\n  <node client_id="left" >\n    <services>\n      <execute command="/local/build-click.sh" shell="sh"/>\n      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>\n    </services>\n    <hardware_type name="pc600"/>\n    <interface client_id="left:if1"/>\n    <interface client_id="left:if2"/>\n  </node>\n  <node client_id="right" >\n    <services>\n      <execute command="/local/build-click.sh" shell="sh"/>\n      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>\n    </services>\n    <hardware_type name="pc600"/>\n    <interface client_id="right:if1"/>\n    <interface client_id="right:if2"/>\n  </node>\n  <node client_id="bottom" >\n    <services>\n      <execute command="/local/build-click.sh" shell="sh"/>\n      <install install_path="/local" url="http://www.gpolab.bbn.com/experiment-support/ClickExampleExperiment/click-example-build-scripts.tgz" file_type="tar.gz"/>\n    </services>\n    <hardware_type name="pc600"/>\n    <interface client_id="bottom:if1"/>\n    <interface client_id="bottom:if2"/>\n    <interface client_id="bottom:if3"/>\n  </node>\n  <node client_id="hostA" >\n    <hardware_type name="pc600"/>\n    <interface client_id="hostA:if1"/>\n  </node>\n  <node client_id="hostB" >\n    <hardware_type name="pc600"/>\n    <interface client_id="hostB:if1"/>\n  </node>\n  <link client_id="link-0">\n    <property source_id="top:if1" dest_id="left:if1" capacity="100000"/>\n    <property source_id="left:if1" dest_id="top:if1" capacity="100000"/>\n    <interface_ref client_id="top:if1"/>\n    <interface_ref client_id="left:if1"/>\n  </link>\n  <link client_id="link-1">\n    <property source_id="top:if2" dest_id="right:if1" capacity="100000"/>\n    <property source_id="right:if1" dest_id="top:if2" capacity="100000"/>\n    <interface_ref client_id="top:if2"/>\n    <interface_ref client_id="right:if1"/>\n  </link>\n  <link client_id="link-2">\n    <property source_id="left:if2" dest_id="bottom:if1" capacity="100000"/>\n    <property source_id="bottom:if1" dest_id="left:if2" capacity="100000"/>\n    <interface_ref client_id="left:if2"/>\n    <interface_ref client_id="bottom:if1"/>\n  </link>\n  <link client_id="link-3">\n    <property source_id="right:if2" dest_id="bottom:if2" capacity="100000"/>\n    <property source_id="bottom:if2" dest_id="right:if2" capacity="100000"/>\n    <interface_ref client_id="right:if2"/>\n    <interface_ref client_id="bottom:if2"/>\n  </link>\n  <link client_id="link-A">\n    <property source_id="hostA:if1" dest_id="top:if3" capacity="100000"/>\n    <property source_id="top:if3" dest_id="hostA:if1" capacity="100000"/>\n    <interface_ref client_id="hostA:if1"/>\n    <interface_ref client_id="top:if3"/>\n  </link>\n  <link client_id="link-B">\n    <property source_id="bottom:if3" dest_id="hostB:if1" capacity="100000"/>\n    <property source_id="hostB:if1" dest_id="bottom:if3" capacity="100000"/>\n    <interface_ref client_id="bottom:if3"/>\n    <interface_ref client_id="hostB:if1"/>\n  </link>\n</rspec>
9	Two nodes with one delay node	GENI	3	Linear topology with delay node in the middle.	<?xml version="1.0" encoding="UTF-8"?>\n<rspec xmlns="http://www.geni.net/resources/rspec/3"\n       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"\n       xmlns:delay="http://www.protogeni.net/resources/rspec/ext/delay/1"\n       xsi:schemaLocation="http://www.geni.net/resources/rspec/3 http://www.geni.net/resources/rspec/3/request.xsd http://www.protogeni.net/resources/rspec/ext/delay/1 http://www.protogeni.net/resources/rspec/ext/delay/1/request-delay.xsd"\n       type="request">\n  <node client_id="PC1">\n    <hardware_type name="pc600"/>\n    <interface client_id="PC1:if0">\n      <ip address="192.168.2.1" netmask="255.255.255.0" type="ipv4"/>\n    </interface>\n  </node>\n  <node client_id="PC2">\n    <hardware_type name="pc600"/>\n    <interface client_id="PC2:if0">\n      <ip address="192.168.2.2" netmask="255.255.255.0" type="ipv4"/>\n    </interface>\n  </node>\n  <node client_id="delay">\n    <hardware_type name="pc600"/>\n    <sliver_type name="delay">\n      <delay:sliver_type_shaping>\n        <delay:pipe source="delay:if0" dest="delay:if1" capacity="1000" packet_loss="0" latency="1"/>\n        <delay:pipe source="delay:if1" dest="delay:if0" capacity="1000" packet_loss="0" latency="1"/>\n      </delay:sliver_type_shaping>\n    </sliver_type>\n    <interface client_id="delay:if0"/>\n    <interface client_id="delay:if1"/>\n  </node>\n  <link client_id="lan0">\n    <interface_ref client_id="delay:if0"/>\n    <interface_ref client_id="PC1:if0"/>\n    <property source_id="delay:if0" dest_id="PC1:if0"/>\n    <property source_id="PC1:if0" dest_id="delay:if0"/>\n    <link_type name="lan"/>\n  </link>\n  <link client_id="lan1">\n    <interface_ref client_id="delay:if1"/>\n    <interface_ref client_id="PC2:if0"/>\n    <property source_id="delay:if1" dest_id="PC2:if0"/>\n    <property source_id="PC2:if0" dest_id="delay:if1"/>\n    <link_type name="lan"/>\n  </link>\n</rspec>
\.


--
-- Data for Name: sa_slice; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY sa_slice (id, slice_id, slice_name, project_id, expiration, owner_id, slice_urn, slice_email, certificate, creation, slice_description) FROM stdin;
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
003	2012-06-27 14:16:11.503505	schema version
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
8	0	https://www.emulab.net:12369/protogeni/xmlrpc/am/2.0	/usr/share/geni-ch/sr/certs/utah-am.pem	ProtoGENI Utah	ProtoGENI Utah AM
9	7		/usr/share/geni-ch/sr/certs/Thawte_Premium_Server_CA.pem		For flack: signer of Utah web server cert
10	0	https://www.pgeni3.gpolab.bbn.com:12369/protogeni/xmlrpc/am/2.0	/usr/share/geni-ch/sr/certs/pgeni3.pem	pgeni3	pgeni3 SW test PG AM in GPO
11	7		/usr/share/geni-ch/sr/certs/pgeni3-ca.pem		pgeni3 CA
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
-- Data for Name: ssh_key; Type: TABLE DATA; Schema: public; Owner: portal
--

COPY ssh_key (id, account_id, filename, description, public_key, private_key) FROM stdin;
1	d34a65ce-8e39-428e-988d-5b0557ba4b4f	id_geni_ssh_rsa	Generated SSH keypair	ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAqDPGd+Efx/drj08a2Ca1TwZ6m+QxpN2Xx0vKLr+TEyuNuHPvC39qpAWa6bO5KtuxgDXfYqXcDAW5Kyp6MW4hxXWcQfD+coGa252XJv0ceafepJ0G2D9/01o5dRgDa02sID/VGidiRSWtdFTz5PrL54UXsU/pPpcR4RXYf8O+9D36B9vI1xcx80PYJCgqrQX/K9Eg1xbs8tlzIjGNXGWLve6U2oVsU1fZ0wy41V95Z5FO7DvcadRWlqCV6gbspUGBWhiXccBO+ABLup/+8Zk6dQCe6u0qN7DoWFw3su4bEJCxS3P0eORlh+AR7ydo9X7ef1P8Y2pJ7YLsvKWsUA6P0Q== www-data@panther.gpolab.bbn.com\n	-----BEGIN RSA PRIVATE KEY-----\nProc-Type: 4,ENCRYPTED\nDEK-Info: DES-EDE3-CBC,A4D855130AF14D3A\n\ntloGuWPL2luXs449inBSQNp3NV8gE1NnBEue8fKYCIADuxQspEK9imveWMFfOZOi\nq/7KYbdB7Lt0K4yRGVRhCTM8VHkr9ie+yQzGai4e7uo0xcL9l0syqHCmYq/LMC1l\ngdXx0MaZolScGxqZeYrPrgUzHVIW82WXS85UwPaVLLsOEsLAC7o9nAWlktXSdRZx\nsBB1TzruN/qrLYFOwNA+wTMNKGLbvOLrsq53GzkoYkBHqTXLGrGNgQQ5G7E4iczo\nOpI8wy2F+/Du0PXhf25KM/QhGJufUUxVM4f0OCx0XPDCQM+uayWQFmzy0dOAE8Cv\npMEx5qihJPWGPBT05SUhv5mvKR/d7yh7VP6mWToVd+PJJ2KEqa1oFar7X7SrWkAR\nRaSgDwtH1bV8Av8mw+sTMkdveadgvvqRa3gIhvCbY/o6GcGapkLn2kb92hPOr77R\notoE0u+JN/XV2fyjL7RW+pFthBkp8Rv3Taf9b5QY/OHTmeaYrW8CSd3bW11AESGh\n+FcZEcG1x59XhUSaj1hU/5gIqHRgW9XpPZLUvDdYMHnDzOBUkbehmOENmJEQX0sq\nI6DUg25i2O3ZJDoKPoFdThMcnI29KAuzpC0WYjV2d+4bjOgJcut/217x7PmsrKDk\nr3fzn9CUBTAB9rwQ58NlV3CFM2iGDix+zA9Rv9MwJOSP23jLTtQKuiDpWmz7OZnW\nvrVKqm4I+0EBF2FjWmYQZUvmGh2vvuFCwEdvAbTqacXKq3zp6NuaUjTy8QM8Pfwr\ncJAEq7SggXVTsC7/9CDaTUsSla9+W/OMcPCyGUsi8oGpRQUuNgHjtM6GL8YrFQUQ\ndU1rumW6Pf/hsY7p/I1DKnj9y3XF+Kpo/uYDhqg3CnXjLFFcrU/EwiBKxnSgFkyh\nsv00xfM+Cyqig7Y+9/Zio6K7s6/mzQYNx/qK1QkMSt9d+NXSY2WOlrjaIAq8UWZ3\nPHKrQnkx+ALNa4b/IsLvlSZake7z4MDY5wboB4HsHJ6+mRrzK6NI9053g0srdumE\nZZKPjhG0UjDDi7bRvSYvg5DolCrYS1aN4NeEvofl0Te+pnWFQpVK5t2ARW9Up8JL\nt5TugyE8JO7Tep2KVIdsLX/phaEIqVTco3B5RapHKSjrRDniG8utGkwKUu4YAhCJ\nlB6rTRgVo07kCyhNjURXdqTuAOhYs2LyTnus0rrRiK38PTb83FGhTEAslT1y2yro\nVQBOur9tToS8/TVwlJfcNCLczOOb78XhoQyp0eStdFLGbzfWnHMfP3WFB112Chkj\nKJoADrhgc516I90pBz8r2oAbFegJkNWtU+NT9GbazwrAgY65ffl4beWyj2ol3e5m\nMnyOjf8xwOjeqKfrIvtaF4jsI+Fmzmo0z5L9+FN9W0ulnER51r2EDz7846K9snNH\nOfgaOHPKmrOu5IO2xsmt8KLgnMhZkYp6BIksDKHShDsI1bn5Vojg9QGdhWDf59u1\n8wRTRAvNJrcN6ejHc0xJ3CBeYyGZzYEYJZ8T9XVLudLVYZ6EG9hOrIUMrFLEtaR8\nzxtWIJKEo8M6v6mDxD+8zjclrfk6nHrnbjn67fmYMNR4s3aIVTT2iA==\n-----END RSA PRIVATE KEY-----\n
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
-- Name: inside_key_account_id_key; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY inside_key
    ADD CONSTRAINT inside_key_account_id_key UNIQUE (account_id);


--
-- Name: logging_entry_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY logging_entry
    ADD CONSTRAINT logging_entry_pkey PRIMARY KEY (id);


--
-- Name: ma_member_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ma_member
    ADD CONSTRAINT ma_member_pkey PRIMARY KEY (id);


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
-- Name: ssh_key_pkey; Type: CONSTRAINT; Schema: public; Owner: portal; Tablespace: 
--

ALTER TABLE ONLY ssh_key
    ADD CONSTRAINT ssh_key_pkey PRIMARY KEY (id);


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
-- Name: inside_key_index_account_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX inside_key_index_account_id ON inside_key USING btree (account_id);


--
-- Name: outside_key_index_account_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX outside_key_index_account_id ON outside_key USING btree (account_id);


--
-- Name: rspec_name; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX rspec_name ON rspec USING btree (name);


--
-- Name: rspec_schema; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX rspec_schema ON rspec USING btree (schema);


--
-- Name: slice_index_owner; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX slice_index_owner ON slice USING btree (owner);


--
-- Name: ssh_key_account_id; Type: INDEX; Schema: public; Owner: portal; Tablespace: 
--

CREATE INDEX ssh_key_account_id ON ssh_key USING btree (account_id);


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
-- Name: inside_key_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY inside_key
    ADD CONSTRAINT inside_key_account_id_fkey FOREIGN KEY (account_id) REFERENCES account(account_id);


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
-- Name: ssh_key_account_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: portal
--

ALTER TABLE ONLY ssh_key
    ADD CONSTRAINT ssh_key_account_id_fkey FOREIGN KEY (account_id) REFERENCES account(account_id);


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

