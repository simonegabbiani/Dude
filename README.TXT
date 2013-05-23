http://dudephp.blogspot.com (Italian Blog)

the "Dude" php framework builder.


/~system/~bin			core files
/~system/~bin/build-index.php	your experience with "dude" starts here
/~system/~runtime/		files copied in the compiled-runtime version of every project (under its ~run folder, you will see..)
/~system/~/			internal use (library cache)
/~system/...			the libraries (Base, WWW, WebMaterTools, etc.)
/HalloWorld.prj			the "Hallo World" project


1. Download the repository as ZIP file from Github

2. Unpack and install in a virtual directory or under the "public" server directory
   (minimum requirement PHP 5.3, but it could run on older versions)

3. IMPORTANT: you must give PHP scripts write permission to local file system

4. From browser, open "http://localhost/......./~system/~bin/build-index.php"
   (or index.html in the root directory)

5. Type the name of your project, eg. "HalloWorld.prj" then press enter

6. When compiled, choose "context" then select..
	.. a) "build all contexts"
	.. b) "build event files"

7. Now, simply choose "execute" link of the context you want

8. To install your application in a server, simply copy all you find under ~run folder)



QUICK TERMINOLOGY:

"Context": the executable parts. The arguments became HTTP query parameters or POST data.

"Asset file": any resource file you use that isn't a source file (e.g. image.png)

"Asset require": any "external" PHP file



BE AWARE:

When you create new context, remember to run a) and b), always

When you modify a source file *WITHOUT MAKING PHP ERRORS* the 
compiler trying to rebuild it automatically, but it is not (yet) 
able to create new contexts if you have created any in the file. 
So, when you type something like <part auto-context="my-context.."> 
remember always to run build-index.php from scratch (rebuild all),
THEN and only after that you can "build all contexts" again in order
to see you new "Context" and able to run.

Regardless of any source code modification, if you make php errors 
the "auto-update" machanism is not able to run and you have to recall 
build-index.php manually.

Never create context name like a source file name, e.g.: if you have
a source file named "index.xml" never create a context named "/index"
or "index" because your application will not work (I apologize).

If you want create new libraries, simply create new folers under /~system/
and put anything you want, BUT BE AWARE on this issue: the namespace of
local file system of the "asset files" (e.g. something like "jquery.min.js") 
it is not unique so you have to give a unique file name.



ABOUT THE LICENSE:

THE SOURCE FILES IN THE FOLLOWING FOLDERS: 
/~system/~bin, /~system/~runtime, /~system/Base, /~system/www, /~system/WebMasterTools

ARE RELEASED UNDER THE Creative Commons BY SA 3.0
http://creativecommons.org/licenses/by-sa/3.0/
SEE LICENSE.TXT

ALL OTHER FILES / LIBRARIES / PACKAGES FOLLOWS THE RESPECTIVE
INTENTION OF THE AUTHORS.

