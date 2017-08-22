# File Contents Search

**Instructions:**  
1. Upload the PHP files to your local development server (never upload these to production)  
   <span>- OR -</span>  
   Use the [search.php](https://github.com/Ultimater/search/blob/one-file/search.php) file from the `one-file` branch instead.
2. Run the `search.php` file in your browser  
3. Type in what to search for, then click **Search**.  

**How it Works**  
This script uses PHP's [glob](http://php.net/manual/en/function.glob.php) to find all files in the specified directory.  
Currently the script is set up so it only looks for `*.php` files, meaning only files with the `.php` extension.  
Then the script proceeds to call [file_get_contents](http://php.net/manual/en/function.file-get-contents.php) on any canditate files it's searching on.  
This script is meant for php-related searches in a cluttered codebase to find the files you're looking for easier.

**Screenshot**  
![file contents](http://i.imgur.com/T0aRLe5.png)
