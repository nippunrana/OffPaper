
Hi, lets start with the ai part. To began with lets built an ai wrapper/function.

Currently, the uploaded or captured image/pdf is uploaded to the server and name is saved in db.

Now, I want after the image upload is complete it should make a gemini 3.5 flash lite api call to get the data from the image to a JSON and then update to the same table in field extracted-json 

There going to be a lot of ai functions or route and many might have multiple ai call flow like we might first make api call to check the type of doc captured and then according make api call for it. This will be done to have better prompting and controlled output. Each might have a different model. So please make a good struture which is easy to maintain and update in future.
