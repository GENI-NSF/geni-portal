#include <Python.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <sys/stat.h>
#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <errno.h>
#include <unistd.h>
#include <syslog.h>
#include <string.h>

/* Note: requires package python-dev */

const char *DEFAULT_PID_FILENAME = "/var/run/geni-pgch.pid";

static const char *pid_file = NULL;

int
write_pid(const char *pid_file)
{
  pid_t pid = getpid();
  FILE *fp = fopen(pid_file, "w");
  if (!fp) {
    char *errmsg = strerror(errno);
    syslog(LOG_ERR, "Error opening pid file %s: %s", pid_file, errmsg);
    return EXIT_FAILURE;
  }
  fprintf(fp, "%d\n", pid);
  fclose(fp);
  return EXIT_SUCCESS;
}

void
delete_pid(void)
{
  syslog(LOG_ERR, "Attempting to delete pid file %s.", pid_file);
  if (unlink(pid_file) != 0)
    {
      /* Log the error, not much else we can do. */
      char *errmsg = strerror(errno);
      syslog(LOG_ERR, "Error deleting pid file %s: %s", pid_file, errmsg);
    }
}

void
termination_handler (int signum)
{
  /* Just do an orderly exit. */
  /* TODO: do this more gracefully, but how to integrate
     with the Python code? */
  exit(1);
}

int
register_sighandlers(void)
{
  signal(SIGTERM, termination_handler);
  return EXIT_SUCCESS;
}

int
run_pgch(char *prog_name)
{
  char *py_args[] = {
    "/usr/share/geni-ch/portal/gcf/src/gcf-pgch.py",
    "-c", "/usr/share/geni-ch/portal/gcf.d/gcf.ini",
    "-p", "8443"
  };
  FILE* file;

  /* TODO: open /var/log/gcf-pgch.log and send STDOUT and STDERR there. */

  Py_SetProgramName(prog_name);  /* optional but recommended */
  Py_Initialize();
  PySys_SetArgv(5, py_args);
  /* check return from fopen */
  file = fopen(py_args[0],"r");
  PyRun_SimpleFile(file, py_args[0]);
  /* Close the file */
  Py_Finalize();
  return(EXIT_SUCCESS);
}

int
main2(int argc, char *argv[])
{
  exit(run_pgch(argv[0]));
}

int
main(int argc, char *argv[])
{
  /* TODO: Get this from the arguments... */
  pid_file = DEFAULT_PID_FILENAME;

  /* Our process ID and Session ID */
  pid_t pid, sid;

  /* Fork off the parent process */
  pid = fork();
  if (pid < 0)
    {
      exit(EXIT_FAILURE);
    }
  /* If we got a good PID, then
     we can exit the parent process. */
  if (pid > 0)
    {
      exit(EXIT_SUCCESS);
    }

  /* Change the file mode mask */
  umask(0);

  /* Open any logs here */

  /* Create a new SID for the child process */
  sid = setsid();
  if (sid < 0)
    {
      /* Log the failure */
      exit(EXIT_FAILURE);
    }



  /* Change the current working directory */
  if ((chdir("/")) < 0)
    {
      /* Log the failure */
      exit(EXIT_FAILURE);
    }

  /* Close out the standard file descriptors */
  close(STDIN_FILENO);
  close(STDOUT_FILENO);
  close(STDERR_FILENO);

  /* Register signal handlers. */
  register_sighandlers();

  /* Write the PID file */
  if (write_pid(pid_file) != EXIT_SUCCESS)
    {
      syslog(LOG_ERR, "Error writing pid file %s. Exiting.", pid_file);
      exit(EXIT_FAILURE);
    }

  /* Arrange to delete the pid file on exit */
  if (atexit(delete_pid) != 0)
    {
      /* exit function registration failed. */
      syslog(LOG_ERR, "Error registering pid deletion. Exiting.");
      exit(EXIT_FAILURE);
    }

  /* Daemon-specific initialization goes here */

  /* Run the ProtoGENI/Clearinghouse adapter until it completes. */
  syslog(LOG_ERR, "Running pgch.");
  int status = run_pgch(argv[0]);
  syslog(LOG_ERR, "Exiting.");
  exit(status);
}
